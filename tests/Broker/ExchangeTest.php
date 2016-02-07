<?php

namespace Radish\Broker;

use Mockery;

class ExchangeTest extends \PHPUnit_Framework_TestCase
{
    public $connection;
    public $exchange;

    public function setUp()
    {
        $this->connection = Mockery::mock('Radish\Broker\Connection');
        $this->exchange = new Exchange($this->connection, 'test_exchange', 'direct', true);
    }

    public function testDeclareExchange()
    {
        $amqpExchange = Mockery::mock('AMQPExchange');

        $amqpExchange->shouldReceive('setName')
            ->with('test_exchange')
            ->once();

        $amqpExchange->shouldReceive('setType')
            ->with('direct')
            ->once();

        $amqpExchange->shouldReceive('setFlags')
            ->with(AMQP_DURABLE)
            ->once();

        $amqpExchange->shouldReceive('declareExchange')
            ->once();

        $this->connection->shouldReceive('createExchange')
            ->andReturn($amqpExchange);

        $this->exchange->declareExchange();
    }

    public function testPublish()
    {
        $amqpExchange = Mockery::mock('AMQPExchange');

        $amqpExchange->shouldReceive('setName')
            ->with('test_exchange')
            ->once();

        $amqpExchange->shouldReceive('setType')
            ->with('direct')
            ->once();

        $amqpExchange->shouldReceive('setFlags')
            ->with(AMQP_DURABLE)
            ->once();

        $amqpExchange->shouldReceive('publish')
            ->with('body', 'routing_key', 1, [])
            ->once();

        $this->connection->shouldReceive('createExchange')
            ->andReturn($amqpExchange)
            ->once();

        $this->exchange->publish('body', 'routing_key', 1, []);
    }

    public function testPublishReusesSameExchangeInstance()
    {
        $amqpExchange = Mockery::mock('AMQPExchange', [
            'setName' => null,
            'setType' => null,
            'setFlags' => null,
        ]);

        $amqpExchange->shouldReceive('publish')
            ->with('body', 'routing_key', 1, [])
            ->twice();

        $this->connection->shouldReceive('createExchange')
            ->andReturn($amqpExchange)
            ->once();

        $this->exchange->publish('body', 'routing_key', 1, []);
        $this->exchange->publish('body', 'routing_key', 1, []);
    }
}
