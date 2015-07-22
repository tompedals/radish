<?php

namespace Radish\Middleware\Ack;

use Psr\Log\LoggerInterface;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\MiddlewareInterface;

class AckMiddleware implements MiddlewareInterface
{
    protected $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        try {
            $return = $next($message, $queue);
            $queue->ack($message);

            if ($this->logger) {
                $this->logger->info(sprintf('Message #%s from queue "%s" has been acknowledged', $message->getDeliveryTag(), $queue->getName()), [
                    'middleware' => 'ack'
                ]);
            }

            return $return;
        } catch (\Exception $exception) {
            $queue->nack($message, false);

            if ($this->logger) {
                $this->logger->warning(sprintf('Exception caught and message #%s from queue "%s" negatively acknowledged', $message->getDeliveryTag(), $queue->getName()), [
                    'middleware' => 'ack',
                    'exception' => $exception
                ]);
            }

            throw $exception;
        }
    }
}
