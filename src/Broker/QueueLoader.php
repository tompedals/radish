<?php

namespace Radish\Broker;

class QueueLoader
{
    /**
     * @var QueueRegistry
     */
    private $queueRegistry;

    /**
     * @param QueueRegistry $queueRegistry
     */
    public function __construct(QueueRegistry $queueRegistry)
    {
        $this->queueRegistry = $queueRegistry;
    }
    /**
     * @param array $queueNames
     * @return QueueCollection
     */
    public function load(array $queueNames)
    {
        $queues = new QueueCollection();

        foreach ($queueNames as $queueName) {
            $queues->add($this->queueRegistry->get($queueName));
        }

        return $queues;
    }
}
