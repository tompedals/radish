<?php

namespace Radish\Middleware;

use Radish\Broker\Message;
use Radish\Broker\Queue;
use SplQueue;

class Next
{
    protected $queue;
    protected $worker;

    public function __construct(array $middlewares, callable $worker)
    {
        // Create the middleware queue
        $this->queue = new SplQueue();
        foreach ($middlewares as $middleware) {
            $this->queue[] = $middleware;
        }

        // Set worker to be called after all of the middleware
        $this->worker = $worker;
    }

    public function __invoke(Message $message, Queue $queue)
    {
        if ($this->queue->isEmpty()) {
            $worker = $this->worker;

            return $worker($message, $queue);
        }

        $middleware = $this->queue->dequeue();

        return $middleware($message, $queue, $this);
    }
}
