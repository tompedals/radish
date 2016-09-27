<?php

namespace Radish\Broker;

class QueueRegistry
{
    protected $connection;
    protected $queueConfigs;
    protected $queues = [];

    public function __construct(Connection $connection, array $queueConfigs)
    {
        $this->connection = $connection;
        $this->queueConfigs = $queueConfigs;
    }

    public function setUp()
    {
        foreach (array_keys($this->queueConfigs) as $queueName) {
            $queue = $this->get($queueName);
            $queue->declareQueue();
            $queue->bind();
        }
    }

    public function get($name)
    {
        if (!isset($this->queues[$name])) {
            $this->queues[$name] = $this->create($name);
        }

        return $this->queues[$name];
    }

    private function create($name)
    {
        $config = $this->getConfig($name);

        // Set up exchange bindings
        // NB: Currently only one binding can be configured
        $bindings = [new QueueBinding($config['exchange'], $config['routing_key'], [])];

        $queue = new Queue(
            $this->connection,
            $name,
            $config['durable'],
            $bindings
        );

        // Set the dead letter exchange
        if (isset($config['dead_letter_exchange'])) {
            $queue->setDeadLetterExchange($config['dead_letter_exchange']);
        }

        // Set max priority
        if (isset($config['max_priority'])) {
            $queue->setMaxPriority($config['max_priority']);
        }

        return $queue;
    }

    private function getConfig($name)
    {
        if (!isset($this->queueConfigs[$name])) {
            throw new \RuntimeException(sprintf('Unknown queue "%s"', $name));
        }

        return $this->queueConfigs[$name];
    }
}
