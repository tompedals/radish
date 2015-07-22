<?php

namespace Radish\Middleware\MaxMessages;

use Psr\Log\LoggerInterface;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\ConfigurableInterface;
use Radish\Middleware\InitializableInterface;
use Radish\Middleware\MiddlewareInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaxMessagesMiddleware implements MiddlewareInterface, ConfigurableInterface, InitializableInterface
{
    protected $logger;
    protected $messagesProcessed;
    protected $maxMessages;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function initialize()
    {
        $this->messagesProcessed = 0;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'limit' => 100
        ]);

        $resolver->setAllowedTypes('limit', 'int');
    }

    public function setOptions(array $options)
    {
        $this->maxMessages = $options['limit'];
    }

    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        $result = $next($message, $queue);

        $this->messagesProcessed++;
        if ($this->messagesProcessed >= $this->maxMessages) {
            if ($this->logger) {
                $this->logger->info(sprintf('Reached maximum message limit of %d', $this->maxMessages));
            }

            return false;
        }

        return $result;
    }
}
