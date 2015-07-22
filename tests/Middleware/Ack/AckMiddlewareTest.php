<?php

namespace Radish\Middleware\Ack;

use Mockery;

class AckMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public $middleware;

    public function setUp()
    {
        $this->middleware = new AckMiddleware();
    }

    /**
     * @dataProvider returnProvider
     */
    public function testAckWhenNoExceptions($return)
    {
        $message = Mockery::mock('Radish\Broker\Message');

        $queue = Mockery::mock('Radish\Broker\Queue');
        $queue->shouldReceive('ack')->with($message)->once();

        $next = function () use ($return) {
            return $return;
        };

        $middleware = $this->middleware;
        $this->assertEquals($return, $middleware($message, $queue, $next));
    }

    public function returnProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @expectedException RuntimeException
     */
    public function testNackWhenExceptionCaught()
    {
        $message = Mockery::mock('Radish\Broker\Message');

        $queue = Mockery::mock('Radish\Broker\Queue');
        $queue->shouldReceive('nack')->with($message, false)->once();

        $next = function () {
            throw new \RuntimeException();
        };

        $middleware = $this->middleware;
        $middleware($message, $queue, $next);
    }
}
