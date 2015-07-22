<?php

namespace Radish\Middleware\Retry;

use Psr\Log\LoggerInterface;
use Radish\Broker\ExchangeRegistry;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\ConfigurableInterface;
use Radish\Middleware\MiddlewareInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RetryMiddleware implements MiddlewareInterface, ConfigurableInterface
{
    protected $exchange;
    protected $exchangeRegistry;
    protected $logger;

    protected $messageOptionsResolver;

    public function __construct(ExchangeRegistry $exchangeRegistry, LoggerInterface $logger = null, OptionsResolver $messageOptionsResolver = null)
    {
        $this->exchangeRegistry = $exchangeRegistry;
        $this->logger = $logger;

        // Initialise the message options resolver
        $this->messageOptionsResolver = $messageOptionsResolver ?: new OptionsResolver();
        $this->configureMessageOptions($this->messageOptionsResolver);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'exchange'
        ]);
    }

    public function setOptions(array $options)
    {
        $this->exchange = $this->exchangeRegistry->get($options['exchange']);
    }

    private function configureMessageOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'max_attempts' => 10
        ]);

        $resolver->setAllowedTypes('max_attempts', 'int');
    }

    private function getMessageOptions(Message $message)
    {
        $options = $message->getHeader('retry_options', []);

        return $this->messageOptionsResolver->resolve($options);
    }

    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        try {
            return $next($message, $queue);
        } catch (\Exception $exception) {
            $options = $this->getMessageOptions($message);
            $attempts = (int) $message->getHeader('retry_attempts', 0);

            if ($attempts < $options['max_attempts']) {
                // Increment the retry attempt counter
                $message->setHeader('retry_attempts', ++$attempts);

                // Exponential backoff liberated from Sidekiq
                // https://github.com/mperham/sidekiq/blob/v3.3.4/lib/sidekiq/middleware/server/retry_jobs.rb#L179
                $message->setExpiration((pow($attempts, 4) + 15 + (rand(1,30) * ($attempts + 1))) * 1000); // milliseconds

                // Remove the original x-death header that interferes with the retry
                $message->removeHeader('x-death');

                // Republish the message onto the retry exchange
                $flags = $message->isMandatory() ? AMQP_MANDATORY : AMQP_NOPARAM;
                $this->exchange->publish($message->getBody(), $message->getRoutingKey(), $flags, $message->getAttributes());

                if ($this->logger) {
                    $this->logger->info(sprintf('Retrying message in %d milliseconds (attempt %d)', $message->getExpiration(), $attempts));
                }
            } else if ($options['max_attempts'] > 0 && $this->logger) {
                $this->logger->error(sprintf('Failed to process message after %d retries', $attempts));
            }

            throw $exception;
        }
    }
}
