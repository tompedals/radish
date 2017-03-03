<?php

namespace Radish\Consumer;

use Psr\Log\LoggerInterface;
use Radish\Broker\QueueCollection;
use Radish\Middleware\InitializableInterface;
use Radish\Middleware\MiddlewareInterface;
use Radish\Middleware\Next;
use Radish\Middleware\SleepyMiddlewareInterface;
use RuntimeException;

class Poller implements ConsumerInterface
{
    protected $queues;
    protected $middlewares;
    protected $workers;
    protected $logger;
    protected $interval;
    protected $waiting = false;

    /**
     * @param QueueCollection $queues
     * @param MiddlewareInterface[] $middlewares
     * @param array $workers
     * @param int $interval
     * @param LoggerInterface|null $logger
     */
    public function __construct(QueueCollection $queues, array $middlewares, array $workers, $interval = 10, LoggerInterface $logger = null)
    {
        $this->queues = $queues;
        $this->middlewares = $middlewares;
        $this->workers = $workers;
        $this->interval = $interval;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function consume()
    {
        if ($this->logger) {
            $this->logger->debug('Starting poller');
        }

        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof InitializableInterface) {
                $middleware->initialize();
            }
        }

        while (true) {
            if ($this->process() === false) {
                break;
            }

            if ($this->sleep() === false) {
                break;
            }
        }

        if ($this->logger) {
            $this->logger->debug('Stopping poller');
        }
    }

    /**
     * @return bool
     */
    private function process()
    {
        while (($message = $this->queues->pop()) !== null) {

            $queue = $this->queues->get($message->getRoutingKey());

            // Process the message using the worker and middleware
            $worker = $this->getWorker($queue->getName());
            $next = new Next($this->middlewares, $worker);

            $result = $next($message, $queue);

            if ($result === false) {
                return false;
            }
        }

        $this->waiting = !isset($message);
        return true;
    }

    /**
     * @return bool
     */
    private function sleep()
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof SleepyMiddlewareInterface && $middleware->sleep() === false) {
                return false;
            }
        }

        if ($this->waiting) {
            // Sleep between queue polls when the queue is empty
            usleep($this->interval * 1000000);
        }
        return true;
    }

    /**
     * @param string $name
     * @return callable
     */
    private function getWorker($name)
    {
        if (!isset($this->workers[$name])) {
            throw new RuntimeException(sprintf('Worker not defined for queue "%s"', $name));
        }

        return $this->workers[$name];
    }
}
