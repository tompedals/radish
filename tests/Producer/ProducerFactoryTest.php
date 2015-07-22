<?php

namespace Radish\Producer;

use Mockery;

class ProducerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public $exchangeRegistry;
    public $factory;

    public function setUp()
    {
        $this->exchangeRegistry = Mockery::mock('Radish\Broker\ExchangeRegistry');
        $this->factory = new ProducerFactory($this->exchangeRegistry);
    }

    public function testCreate()
    {
        $exchange = Mockery::mock('Radish\Broker\Exchange');

        $this->exchangeRegistry->shouldReceive('get')
            ->with('exchange_name')
            ->andReturn($exchange)
            ->once();

        $producer = $this->factory->create('exchange_name');

        $this->assertInstanceOf('Radish\Producer\ProducerInterface', $producer);
    }
}
