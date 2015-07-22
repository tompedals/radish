<?php

namespace Radish\Broker;

class ExchangeRegistry
{
    protected $connection;
    protected $exchangeConfigs;
    protected $exchanges = [];

    public function __construct(Connection $connection, array $exchangeConfigs)
    {
        $this->connection = $connection;
        $this->exchangeConfigs = $exchangeConfigs;
    }

    public function setUp()
    {
        foreach (array_keys($this->exchangeConfigs) as $exchangeName) {
            $this->get($exchangeName)->declareExchange();
        }
    }

    public function get($name)
    {
        if (!isset($this->exchanges[$name])) {
            $this->exchanges[$name] = $this->create($name);
        }

        return $this->exchanges[$name];
    }

    private function create($name)
    {
        $config = $this->getConfig($name);

        return new Exchange(
            $this->connection,
            $name,
            $config['type'],
            $config['durable']
        );
    }

    private function getConfig($name)
    {
        if (!isset($this->exchangeConfigs[$name])) {
            throw new \RuntimeException(sprintf('Unknown exchange "%s"', $name));
        }

        return $this->exchangeConfigs[$name];
    }
}
