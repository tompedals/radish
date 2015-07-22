<?php

namespace Radish\Producer;

use Mockery;
use Radish\Broker\Message;

class ProducerTest extends \PHPUnit_Framework_TestCase
{
    public $exchange;
    public $producer;

    public function setUp()
    {
        $this->exchange = Mockery::mock('Radish\Broker\Exchange');
        $this->producer = new Producer($this->exchange);
    }

    public function testPublish()
    {
        $message = new Message();
        $message->setBody('test');
        $message->setRoutingKey('routing');

        $this->exchange->shouldReceive('publish')
            ->with(
                $message->getBody(),
                $message->getRoutingKey(),
                AMQP_NOPARAM,
                $message->getAttributes()
            )
            ->once();

        $this->producer->publish($message);
    }
}
