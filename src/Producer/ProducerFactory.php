<?php

namespace Radish\Producer;

use Radish\Broker\ExchangeRegistry;

class ProducerFactory implements ProducerFactoryInterface
{
    protected $exchangeRegistry;

    public function __construct(ExchangeRegistry $exchangeRegistry)
    {
        $this->exchangeRegistry = $exchangeRegistry;
    }

    public function create($exchangeName)
    {
        $exchange = $this->exchangeRegistry->get($exchangeName);

        return new Producer($exchange);
    }
}
