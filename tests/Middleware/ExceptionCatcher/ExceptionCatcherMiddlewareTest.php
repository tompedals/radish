<?php

namespace Radish\Middleware\ExceptionCatcher;

use Mockery;

class ExceptionCatcherMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public $middleware;

    public function setUp()
    {
        $this->middleware = new ExceptionCatcherMiddleware();
    }

    /**
     * @dataProvider returnProvider
     */
    public function testWhenNoExceptions($return)
    {
        $message = Mockery::mock('Radish\Broker\Message');
        $queue = Mockery::mock('Radish\Broker\Queue');

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

    public function testCatchesExceptions()
    {
        $message = Mockery::mock('Radish\Broker\Message');
        $queue = Mockery::mock('Radish\Broker\Queue');

        $next = function () {
            throw new \RuntimeException();
        };

        $middleware = $this->middleware;
        $middleware($message, $queue, $next);
    }
}
