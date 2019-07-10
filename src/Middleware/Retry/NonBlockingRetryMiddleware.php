<?php

namespace Radish\Middleware\Retry;

use Exception;
use Psr\Log\LoggerInterface;
use Radish\Broker\ExchangeInterface;
use Radish\Broker\ExchangeRegistry;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\ConfigurableInterface;
use Radish\Middleware\MiddlewareInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Based on RetryMiddleware, but prevents messages further down the queue with shorter expirations being blocked by
 * messages at the head of the queue with longer expirations.
 *
 * Sets the message expiration to be 1 minute and sets a custom header with the actual retry timestamp
 * (using existing exponential back-off logic), which is compared to determine if the message should be processed now or
 * put back onto the retry queue. Processing may still start slightly after the retry timestamp due to only being
 * popped off the queue each minute, but does not suffer from the same potentially long delays as RetryMiddleware.
 */
class NonBlockingRetryMiddleware implements MiddlewareInterface, ConfigurableInterface
{
    /**
     * @var ExchangeInterface
     */
    private $exchange;
    /**
     * @var ExchangeRegistry
     */
    private $exchangeRegistry;
    /**
     * @var LoggerInterface|null
     */
    private $logger;
    /**
     * @var OptionsResolver
     */
    private $messageOptionsResolver;

    /**
     * @param ExchangeRegistry $exchangeRegistry
     * @param LoggerInterface|null $logger
     * @param OptionsResolver|null $messageOptionsResolver
     */
    public function __construct(
        ExchangeRegistry $exchangeRegistry,
        LoggerInterface $logger = null,
        OptionsResolver $messageOptionsResolver = null
    ) {
        $this->exchangeRegistry = $exchangeRegistry;
        $this->logger = $logger;

        // Initialise the message options resolver
        $this->messageOptionsResolver = $messageOptionsResolver ?: new OptionsResolver();
        $this->configureMessageOptions($this->messageOptionsResolver);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['exchange']);
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options)
    {
        $this->exchange = $this->exchangeRegistry->get($options['exchange']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureMessageOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['max_attempts' => 10]);
        $resolver->setAllowedTypes('max_attempts', 'int');
    }

    /**
     * @param Message $message
     * @return array
     */
    private function getMessageOptions(Message $message)
    {
        $options = $message->getHeader('retry_options', []);
        return $this->messageOptionsResolver->resolve($options);
    }

    /**
     * Exponential backoff liberated from Sidekiq
     * https://github.com/mperham/sidekiq/blob/v3.3.4/lib/sidekiq/middleware/server/retry_jobs.rb#L179
     *
     * @param int $attempts
     * @return int The number of seconds to wait until next attempt
     */
    private function getBackoffPeriod(int $attempts)
    {
        return (pow($attempts, 4) + 15 + (rand(1, 30) * ($attempts + 1)));
    }

    /**
     * @param Message $message
     */
    private function sendMessageToRetryQueue(Message $message)
    {
        // Remove the original x-death header that interferes with the retry
        $message->removeHeader('x-death');

        $message->setExpiration(60000); // tell RabbitMQ to move message back to normal queue after one minute

        // Republish the message onto the retry exchange
        $flags = $message->isMandatory() ? AMQP_MANDATORY : AMQP_NOPARAM;
        $this->exchange->publish($message->getBody(), $message->getRoutingKey(), $flags, $message->getAttributes());
    }

    /**
     * @inheritDoc
     */
    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        $options = $this->getMessageOptions($message);

        $attempts = (int) $message->getHeader('retry_attempts', 0);

        // For BC - set the retry_at header if the message has already been on the retry queue prior to release
        // if we've got here it will be because the old expiry has been reached so set retry_at to current time
        if ($attempts > 0 && $message->getHeader('retry_at') === null) {
            $message->setHeader('retry_at', time());
        }

        // Check to see if we're ready to process the message, will be true if either the message isn't retryable
        // or if it is and the retry_at timestamp has been reached
        $shouldProcess = (int)$options['max_attempts'] === 0 || (int)$message->getHeader('retry_at', 0) <= time();
        if (!$shouldProcess) {
            $this->sendMessageToRetryQueue($message);
            return true;
        }

        try {
            return $next($message, $queue);
        } catch (Exception $exception) {
            if ($attempts < $options['max_attempts']) {
                // Increment the retry attempt counter
                $message->setHeader('retry_attempts', ++$attempts);

                // Set a custom header to indicate when we want to attempt to processing next.
                // If this hasn't been reached, we'll put the message back on the retry queue.
                $backoffPeriod = $this->getBackoffPeriod($attempts);
                $message->setHeader('retry_at', time() + $backoffPeriod);

                $this->sendMessageToRetryQueue($message);

                if ($this->logger) {
                    $this->logger->info(
                        sprintf('Retrying message in %d seconds (attempt %d)', $backoffPeriod, $attempts)
                    );
                }
            } elseif ($options['max_attempts'] > 0 && $this->logger) {
                $this->logger->critical(sprintf('Failed to process message after %d retries', $attempts));
            }

            throw $exception;
        }
    }
}
