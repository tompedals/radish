<?php

namespace Radish\Middleware;

use Mockery;

class NextTest extends \PHPUnit_Framework_TestCase
{
    public function testCallsWorkerWhenNoMiddleware()
    {
        $message = Mockery::mock('Radish\Broker\Message');
        $queue = Mockery::mock('Radish\Broker\Queue');

        $workerCalled = false;
        $worker = function () use (&$workerCalled) {
            $workerCalled = true;
        };

        $next = new Next([], $worker);
        $next($message, $queue);

        $this->assertTrue($workerCalled);
    }

    public function testCallsMiddlewareInOrder()
    {
        $message = Mockery::mock('Radish\Broker\Message');
        $queue = Mockery::mock('Radish\Broker\Queue');

        $callees = [];

        $middlewares[] = function ($message, $queue, $next) use (&$callees) {
            $callees[] = 'middleware1';
            $next($message, $queue);
        };

        $middlewares[] = function ($message, $queue, $next) use (&$callees) {
            $callees[] = 'middleware2';
            $next($message, $queue);
        };

        $worker = function () use (&$callees) {
            $callees[] = 'worker';
        };

        $next = new Next($middlewares, $worker);
        $next($message, $queue);

        $this->assertEquals(['middleware1', 'middleware2', 'worker'], $callees);
    }
}
