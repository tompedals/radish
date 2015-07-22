<?php

namespace Radish\Broker;

class QueueCollection implements ConsumableInterface
{
    protected $queues;

    public function __construct(array $queues = [])
    {
        $this->queues = [];
        foreach ($queues as $queue) {
            $this->add($queue);
        }
    }

    public function add(Queue $queue)
    {
        $this->queues[$queue->getName()] = $queue;
    }

    public function get($name)
    {
        if (!isset($this->queues[$name])) {
            throw new \InvalidArgumentException(sprintf('Queue not found "%s"', $name));
        }

        return $this->queues[$name];
    }

    public function consume(callable $callback = null)
    {
        $index = 0;
        $queueCount = count($this->queues);
        foreach ($this->queues as $queue) {
            if ($index === $queueCount - 1) {
                // Only pass the callback to the last queue
                $queue->consume($callback);
            } else {
                $queue->consume(null);
            }

            $index++;
        }

        // Cancel when finished consuming
        $this->cancel();
    }

    public function cancel()
    {
        foreach ($this->queues as $queue) {
            $queue->cancel();
        }
    }
}
