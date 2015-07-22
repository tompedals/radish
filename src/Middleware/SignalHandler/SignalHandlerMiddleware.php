<?php

namespace Radish\Middleware\SignalHandler;

use Psr\Log\LoggerInterface;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\MiddlewareInterface;

class SignalHandlerMiddleware implements MiddlewareInterface
{
    protected $logger;
    protected $shutdown = false;
    protected $signals = [SIGTERM, SIGQUIT, SIGINT];

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        foreach ($this->signals as $signal) {
            // Capture signals to tell the consumer to cancel
            pcntl_signal($signal, [$this, 'shutdown']);
        }

        $return = $next($message, $queue);

        pcntl_signal_dispatch();

        foreach ($this->signals as $signal) {
            // Restore signals to their default handlers
            pcntl_signal($signal, SIG_DFL);
        }

        if ($this->shutdown) {
            if ($this->logger) {
                $this->logger->debug('Shutting down');
            }

            return false;
        }

        return $return;
    }

    public function shutdown()
    {
        $this->shutdown = true;
    }
}
