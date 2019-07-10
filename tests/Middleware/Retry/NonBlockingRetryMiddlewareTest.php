<?php

namespace Radish\Middleware\Retry;

use Mockery;
use Mockery\Mock;
use Psr\Log\LoggerInterface;
use Radish\Broker\ExchangeInterface;
use Radish\Broker\ExchangeRegistry;
use Radish\Broker\Message;
use RuntimeException;

class NonBlockingRetryMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Mock|ExchangeInterface
     */
    public $exchange;
    /**
     * @var Mock|ExchangeRegistry
     */
    public $exchangeRegistry;
    /**
     * @var NonBlockingRetryMiddleware
     */
    public $middleware;
    /**
     * @var Mock|LoggerInterface
     */
    private $logger;

    public function setUp()
    {
        $this->exchange = Mockery::mock('Radish\Broker\Exchange', [
            'publish' => null,
        ]);
        $this->exchangeRegistry = Mockery::mock('Radish\Broker\ExchangeRegistry', [
            'get' => $this->exchange,
        ]);
        $this->logger = Mockery::mock('Psr\Log\LoggerInterface', [
            'info' => null,
            'critical' => null,
        ]);

        $this->middleware = new NonBlockingRetryMiddleware($this->exchangeRegistry, $this->logger);
        $this->middleware->setOptions([
            'exchange' => 'test',
        ]);
    }

    /**
     * @dataProvider returnProvider
     */
    public function testWhenNoExceptions($return)
    {
        $message = Mockery::mock('Radish\Broker\Message');
        $message->shouldReceive('getHeader')
            ->andReturnUsing(function ($name, $default) {
                return $default;
            });
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

    public function testSetsRetryAtHeaderForMessagesAlreadyInRetryQueue()
    {
        $currentTimestamp = time();
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_attempts', 3);
        $message->setHeader('retry_options', [
            'max_attempts' => 5
        ]);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            return true;
        };
        $middleware = $this->middleware;

        $this->exchange->shouldReceive('publish')->never();

        $middleware($message, $queue, $next);

        $this->assertArrayHasKey('retry_at', $message->getHeaders());
        $this->assertGreaterThanOrEqual($currentTimestamp, $message->getHeader('retry_at'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRepublishesMessageWithFixedExpiration()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_options', [
            'max_attempts' => 5
        ]);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) {
                return isset($attributes['expiration']) && $attributes['expiration'] === 60000;
            }))
            ->once();

        $middleware($message, $queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRepublishesMessageWithRetryHeader()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_options', [
            'max_attempts' => 5
        ]);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) {
                return isset($attributes['headers']['retry_at']);
            }))
            ->once();

        $middleware($message, $queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRepublishesMessageWithIncreasedRetryInterval()
    {
        $currentTimestamp = time();
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_attempts', 3);
        $message->setHeader('retry_at', $currentTimestamp);
        $message->setHeader('retry_options', [
            'max_attempts' => 5
        ]);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) use ($currentTimestamp) {
                return $attributes['headers']['retry_at'] >= $currentTimestamp + 276 && //lower boundary of back-off calculation
                    $attributes['headers']['retry_at'] <= $currentTimestamp + 421; //upper boundary of back-off calculation
            }))
            ->once();

        $middleware($message, $queue, $next);
    }

    public function testRepublishesMessageWithoutIncreasingRetryIntervalOrRetryCountIfNotReadyToBeProcessed()
    {
        $currentTimestamp = time();
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_attempts', 3);
        $message->setHeader('retry_at', $currentTimestamp *2); // set far in the future so won't get processed
        $message->setHeader('retry_options', [
            'max_attempts' => 5
        ]);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) use ($currentTimestamp) {
                return $attributes['headers']['retry_attempts'] === 3 &&
                    $attributes['headers']['retry_at'] === $currentTimestamp * 2;
            }))
            ->once();

        $middleware($message, $queue, $next);
    }

    public function testRepublishesMessageWithoutLoggingIfNotReadyToBeProcessed()
    {
        $currentTimestamp = time();
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_attempts', 3);
        $message->setHeader('retry_at', $currentTimestamp *2); // set far in the future so won't get processed
        $message->setHeader('retry_options', [
            'max_attempts' => 5
        ]);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        $this->logger->shouldReceive('info')->never();

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
        $message->setHeader('retry_options', [
            'max_attempts' => 5
        ]);
        $message->setHeader('x-death', []);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) {
                return !isset($attributes['headers']['x-death']);
            }))
            ->once();

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
        $message->setHeader('retry_options', [
            'max_attempts' => 5
        ]);
        $message->setHeader('x-death', []);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        $this->exchange->shouldReceive('publish')
            ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) {
                return isset($attributes['headers']['retry_attempts']) && $attributes['headers']['retry_attempts'] === 1;
            }))
            ->once();

        $middleware($message, $queue, $next);
    }

    public function retryAttemptsDataProvider()
    {
        // headers, shouldRepublish
        return [
            [['retry_attempts' => 5, 'retry_options'=> ['max_attempts' => 5]], false],
            [['retry_attempts' => 3, 'retry_options'=> ['max_attempts' => 5]], true],
            [['retry_attempts' => 10], false], // max_attempts defaults to 10
            [['retry_attempts' => 9], true], // max_attempts defaults to 10
            [['retry_options' => ['max_attempts' => 0]], false],
        ];
    }

    /**
     * @dataProvider retryAttemptsDataProvider
     * @expectedException RuntimeException
     */
    public function testRetryLimitsAreRespected($headers, $shouldRepublish)
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeaders($headers);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        if ($shouldRepublish) {
            $this->exchange->shouldReceive('publish')
                ->with('body', 'key', AMQP_NOPARAM, Mockery::on(function ($attributes) use ($headers) {
                    return $attributes['headers']['retry_attempts'] === $headers['retry_attempts'] + 1;
                }))
                ->once();
        } else {
            $this->exchange->shouldReceive('publish')
                ->never();
        }

        $middleware($message, $queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCriticalErrorIsLoggedWhenRetryLimitReached()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_attempts', 5);
        $message->setHeader('retry_options', ['max_attempts' => 5]);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        $this->logger->shouldReceive('critical')
            ->once()
            ->with('Failed to process message after 5 retries');

        $middleware($message, $queue, $next);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInfoMessageIsLoggedWhenMessageRequeuedDueToRetryFailure()
    {
        $message = new Message();
        $message->setBody('body');
        $message->setRoutingKey('key');
        $message->setHeader('retry_attempts', 4);
        $message->setHeader('retry_options', ['max_attempts' => 5]);
        $queue = Mockery::mock('Radish\Broker\Queue');
        $next = function () {
            throw new \RuntimeException();
        };
        $middleware = $this->middleware;

        $this->logger->shouldReceive('info')
            ->once()
            ->with(Mockery::on(
                function ($message) {
                    return preg_match('/Retrying message in \d+ seconds \(attempt 5\)/', $message) === 1;
                }

            ));

        $middleware($message, $queue, $next);
    }
}
