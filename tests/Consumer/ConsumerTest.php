<?php

namespace Radish\Consumer;

use Mockery;

class ConsumerTest extends \PHPUnit_Framework_TestCase
{
    public $queues;

    public function setUp()
    {
        $this->queues = Mockery::mock('Radish\Broker\QueueCollection', [
            'consume' => null
        ]);
    }

    public function testConsume()
    {
        $consumer = new Consumer($this->queues, [], []);

        $this->queues->shouldReceive('consume')
            ->with([$consumer, 'process'])
            ->once();

        $consumer->consume();
    }

    public function testConsumeShouldInitializeMiddleware()
    {
        $middleware = Mockery::mock('Radish\Middleware\InitializableInterface');
        $middleware->shouldReceive('initialize')
            ->once();

        $consumer = new Consumer($this->queues, [$middleware], []);
        $consumer->consume();
    }

    public function testProcess()
    {
        $queueName = 'test_message';

        $queue = Mockery::mock('Radish\Broker\Queue', [
            'getName' => $queueName
        ]);

        $this->queues->shouldReceive('get')
            ->with($queueName)
            ->andReturn($queue);

        $message = Mockery::mock('Radish\Broker\Message', [
            'getRoutingKey' => $queueName
        ]);

        $workerCalled = false;

        $workers = [
            $queueName => function () use (&$workerCalled) {
                $workerCalled = true;
            }
        ];

        $consumer = new Consumer($this->queues, [], $workers);
        $consumer->process($message);

        $this->assertTrue($workerCalled);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Worker not defined for queue
     */
    public function testProcessWhenWorkerNotAvailable()
    {
        $queueName = 'test_message';

        $queue = Mockery::mock('Radish\Broker\Queue', [
            'getName' => $queueName
        ]);

        $this->queues->shouldReceive('get')
            ->with($queueName)
            ->andReturn($queue);

        $message = Mockery::mock('Radish\Broker\Message', [
            'getRoutingKey' => $queueName
        ]);

        $consumer = new Consumer($this->queues, [], []);
        $consumer->process($message);
    }
}