<?php

namespace Radish\Broker;

use AMQPQueue;
use Mockery;
use Mockery\Mock;
use PHPUnit_Framework_TestCase;

class QueueTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Queue
     */
    private $queue;
    /**
     * @var Mock|Connection
     */
    private $connection;
    /**
     * @var Mock|AMQPQueue
     */
    private $amqpQueue;

    public function setUp()
    {
        $this->amqpQueue = Mockery::mock('AMQPQueue', [
            'declareQueue' => null,
            'setName' => null,
            'setFlags' => null,
        ]);

        $this->connection = Mockery::mock('Radish\Broker\Connection', [
            'createQueue' => $this->amqpQueue,
        ]);
        
        $this->queue = new Queue($this->connection, 'test_queue', true, []);
    }

    public function testDeclareQueueSetsMaxPriorityArgOnAmqpQueue()
    {
        $this->queue->setMaxPriority(100);

        $this->amqpQueue->shouldReceive('setArgument')
            ->with('x-max-priority', 100)
            ->once();

        $this->queue->declareQueue();
    }

    public function testDeclareQueueDoesntSetMaxPriorityOnAmqpWhenNull()
    {
        $this->queue->setMaxPriority(null);

        $this->amqpQueue->shouldReceive('setArgument')
            ->never();

        $this->queue->declareQueue();
    }
}
