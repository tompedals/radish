<?php

namespace Radish\Middleware\Ack;

use Mockery;
use RuntimeException;

class AckMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public $logger;
    public $middleware;
    public $message;
    public $queue;

    public function setUp()
    {
        $this->logger = Mockery::mock('Psr\Log\LoggerInterface', [
            'info' => null,
            'warning' => null,
        ]);

        $this->middleware = new AckMiddleware();

        $this->message = Mockery::mock('Radish\Broker\Message', [
            'getDeliveryTag' => '1'
        ]);

        $this->queue = Mockery::mock('Radish\Broker\Queue', [
            'getName' => 'test',
            'ack' => null,
            'nack' => null,
        ]);
    }

    /**
     * @dataProvider returnProvider
     */
    public function testAckWhenNoExceptions($return)
    {
        $this->queue->shouldReceive('ack')->with($this->message)->once();

        $next = function () use ($return) {
            return $return;
        };

        $middleware = $this->middleware;
        $this->assertEquals($return, $middleware($this->message, $this->queue, $next));
    }

    public function returnProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    public function testAckWhenNoExceptionsLogsInfo()
    {
        $this->logger->shouldReceive('info')
            ->with('Message #1 from queue "test" has been acknowledged', [
                'middleware' => 'ack'
            ])
            ->once();

        $next = function () {
            return true;
        };

        $middleware = new AckMiddleware($this->logger);
        $middleware($this->message, $this->queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testNackWhenExceptionCaught()
    {
        $this->queue->shouldReceive('nack')->with($this->message, false)->once();

        $next = function () {
            throw new RuntimeException();
        };

        $middleware = $this->middleware;
        $middleware($this->message, $this->queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testNackWhenExceptionCaughtLogsException()
    {
        $exception = new RuntimeException();

        $this->logger->shouldReceive('warning')
            ->with('Exception caught and message #1 from queue "test" negatively acknowledged', [
                'middleware' => 'ack',
                'exception' => $exception,
            ])
            ->once();

        $next = function () use ($exception) {
            throw $exception;
        };

        $middleware = new AckMiddleware($this->logger);
        $middleware($this->message, $this->queue, $next);
    }
}
