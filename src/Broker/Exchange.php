<?php

namespace Radish\Broker;

class Exchange
{
    protected $exchange;
    protected $connection;
    protected $name;
    protected $type;
    protected $durable;

    public function __construct(Connection $connection, $name, $type, $durable = true)
    {
        $this->connection = $connection;
        $this->name = $name;
        $this->type = $type;
        $this->durable = $durable;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isDurable()
    {
        return $this->durable;
    }

    public function declareExchange()
    {
        $this->getAMQPExchange()->declareExchange();
    }

    public function publish($body, $routingKey, $flags, array $attributes)
    {
        $this->getAMQPExchange()->publish($body, $routingKey, $flags, $attributes);
    }

    private function getAMQPExchange()
    {
        if ($this->exchange === null) {
            // Lazyily connects and creates the channel
            $this->exchange = $this->connection->createExchange();
            $this->exchange->setName($this->getName());
            $this->exchange->setType($this->getType());
            $this->exchange->setFlags($this->isDurable() ? AMQP_DURABLE : AMQP_PASSIVE);
        }

        return $this->exchange;
    }
}
