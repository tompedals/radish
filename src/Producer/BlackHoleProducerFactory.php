<?php

namespace Radish\Producer;

use Radish\Broker\ExchangeRegistry;

class BlackHoleProducerFactory implements ProducerFactoryInterface
{
    public function create($exchangeName)
    {
        return new BlackHoleProducer();
    }
}
