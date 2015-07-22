<?php

namespace Radish\Broker;

class QueueBinding
{
    protected $exchangeName;
    protected $routingKey;
    protected $arguments;

    public function __construct($exchangeName, $routingKey, array $arguments)
    {
        $this->exchangeName = $exchangeName;
        $this->routingKey = $routingKey;
        $this->arguments = $arguments;
    }

    public function getExchangeName()
    {
        return $this->exchangeName;
    }

    public function setExchangeName($exchangeName)
    {
        $this->exchangeName = $exchangeName;
    }

    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }
}
