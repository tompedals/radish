<?php

namespace Radish\Middleware\Retry;

use Mockery;
use Radish\Broker\Message;

class RetryMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public $exchange;
    public $exchangeRegistry;
    public $middleware;

    public function setUp()
    {
        $this->exchange = Mockery::mock('Radish\Broker\Exchange');
        $this->exchangeRegistry = Mockery::mock('Radish\Broker\ExchangeRegistry', [
            'get' => $this->exchange
        ]);

        $this->middleware = new RetryMiddleware($this->exchangeRegistry);
        $this->middleware->setOptions([
            'exchange' => 'test'
        ]);
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

    /**
     * @expectedException RuntimeException
     */
    public function testRepublishesMessageWithExpiration()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');

        $queue = Mockery::mock('Radish\Broker\Queue');

        $next = function () {
            throw new \RuntimeException();
        };

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) {
                return isset($attributes['expiration']) && $attributes['expiration'] > 0;
            }))
            ->once();

        $middleware = $this->middleware;
        $middleware($message, $queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRemovesXDeathHeaderBeforeRepublishing()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('x-death', []);

        $queue = Mockery::mock('Radish\Broker\Queue');

        $next = function () {
            throw new \RuntimeException();
        };

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) {
                return !isset($attributes['headers']['x-death']);
            }))
            ->once();

        $middleware = $this->middleware;
        $middleware($message, $queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSetsRetryAttemptHeader()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('x-death', []);

        $queue = Mockery::mock('Radish\Broker\Queue');

        $next = function () {
            throw new \RuntimeException();
        };

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) {
                return isset($attributes['headers']['retry_attempts']);
            }))
            ->once();

        $middleware = $this->middleware;
        $middleware($message, $queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIncrementsRetryAttemptHeader()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_attempts', 3);

        $queue = Mockery::mock('Radish\Broker\Queue');

        $next = function () {
            throw new \RuntimeException();
        };

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) {
                return $attributes['headers']['retry_attempts'] === 4;
            }))
            ->once();

        $middleware = $this->middleware;
        $middleware($message, $queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testDoesNotRepublishIfRetryMaxAttemptsReached()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_attempts', 5);
        $message->setHeader('retry_options', [
            'max_attempts' => 5
        ]);

        $queue = Mockery::mock('Radish\Broker\Queue');

        $next = function () {
            throw new \RuntimeException();
        };

        $this->exchange->shouldReceive('publish')
            ->never();

        $middleware = $this->middleware;
        $middleware($message, $queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testDoesNotRetryIfMaxAttemptsIsZero()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_options', [
            'max_attempts' => 0
        ]);

        $queue = Mockery::mock('Radish\Broker\Queue');

        $next = function () {
            throw new \RuntimeException();
        };

        $this->exchange->shouldReceive('publish')
            ->never();

        $middleware = $this->middleware;
        $middleware($message, $queue, $next);
    }
}
