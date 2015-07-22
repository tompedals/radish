<?php

namespace Radish\Consumer;

use Radish\Broker\Message;
use Radish\Broker\QueueCollection;
use Radish\Middleware\InitializableInterface;
use Radish\Middleware\MiddlewareInterface;
use Radish\Middleware\Next;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Consumer implements ConsumerInterface
{
    protected $queues;
    protected $middlewares;
    protected $workers;
    protected $logger;

    public function __construct(QueueCollection $queues, array $middlewares, array $workers, LoggerInterface $logger = null)
    {
        $this->queues = $queues;
        $this->middlewares = $middlewares;
        $this->workers = $workers;
        $this->logger = $logger;
    }

    public function consume()
    {
        if ($this->logger) {
            $this->logger->debug('Starting consumer');
        }

        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof InitializableInterface) {
                $middleware->initialize();
            }
        }

        $this->queues->consume([$this, 'process']);
    }

    public function process(Message $message)
    {
        if ($this->logger) {
            $this->logger->debug('Processing message', [
                'body' => $message->getBody(),
                'headers' => $message->getHeaders(),
                'exchange_name' => $message->getExchangeName(),
                'routing_key' => $message->getRoutingKey()
            ]);
        }

        // Determine the queue from the message routing key (expects direct exchange)
        $queue = $this->queues->get($message->getRoutingKey());

        // Process the message using the worker and middleware
        $worker = $this->getWorker($queue->getName());
        $next = new Next($this->middlewares, $worker);

        return $next($message, $queue);
    }

    private function getWorker($name)
    {
        if (!isset($this->workers[$name])) {
            throw new \RuntimeException(sprintf('Worker not defined for queue "%s"', $name));
        }

        return $this->workers[$name];
    }
}
