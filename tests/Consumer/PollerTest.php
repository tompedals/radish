<?php

namespace Radish\Consumer;

use Mockery;
use Mockery\Mock;
use PHPUnit_Framework_TestCase;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Broker\QueueCollection;

class PollerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mock|QueueCollection
     */
    private $queues;
    /**
     * @var Mock|Message
     */
    private $message;
    /**
     * @var Mock|Queue
     */
    private $queue;

    public function setUp()
    {
        $this->message = Mockery::mock(Message::class, [
            'getRoutingKey' => 'abc'
        ]);
        $this->queue = Mockery::mock(Queue::class, [
            'getName' => 'abc'
        ]);
        $this->queues = Mockery::mock(QueueCollection::class, [
            'consume' => null,
            'pop' => $this->message,
            'get' => $this->queue,
        ]);
    }

    public function testConsume()
    {
        $this->queues->shouldReceive('pop')
            ->andReturn(
                $this->message,
                null
            );

        $this->queues->shouldReceive('get')
            ->with('abc')
            ->once()
            ->andReturn($this->queue);

        $workerCalled = false;

        $workers = [
            'abc' => function () use (&$workerCalled) {
                $workerCalled = true;
                return false;
            }
        ];

        $consumer = new Poller($this->queues, [], $workers);
        $consumer->consume();

        $this->assertTrue($workerCalled);
    }
}
