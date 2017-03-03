<?php

namespace Radish\Broker;

class QueueCollection implements ConsumableInterface
{
    /**
     * @var Queue[]
     */
    protected $queues;

    /**
     * @var int
     */
    private $queueCounter = 0;

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

    /**
     * @return Message|null
     */
    public function pop()
    {
        $keys = array_keys($this->queues);
        if (!isset($keys[$this->queueCounter])) {
            $this->queueCounter = 0;
        }

        $queue = $this->queues[$keys[$this->queueCounter]];

        $message = $queue->pop();
        $this->queueCounter++;

        if ($message !== null) {
            return $message;
        }

        return null;
    }

    public function cancel()
    {
        foreach ($this->queues as $queue) {
            $queue->cancel();
        }
    }


}
