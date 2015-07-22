<?php

namespace Radish\Middleware\ExceptionCatcher;

use Psr\Log\LoggerInterface;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\MiddlewareInterface;

class ExceptionCatcherMiddleware implements MiddlewareInterface
{
    protected $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        try {
            return $next($message, $queue);
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->error(sprintf('Exception caught when processing message #%s from queue "%s"', $message->getDeliveryTag(), $queue->getName()), [
                    'middleware' => 'exception_catcher',
                    'exception' => $exception
                ]);
            }
        }
    }
}
