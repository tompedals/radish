<?php

namespace Radish\Middleware\MemoryLimit;

use Mockery;
use Radish\Broker\Message;

class MemoryLimitMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public $logger;
    public $message;
    public $queue;

    public function setUp()
    {
        $this->logger = Mockery::mock('Psr\Log\LoggerInterface', [
            'info' => null
        ]);

        $this->message = Mockery::mock('Radish\Broker\Message');
        $this->queue = Mockery::mock('Radish\Broker\Queue');
    }

    public function returnProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @dataProvider returnProvider
     */
    public function testWhenNoMemoryLimit($return)
    {
        $middleware = new MemoryLimitMiddleware($this->logger);

        $this->assertEquals($return, $middleware($this->message, $this->queue, function () use ($return) {
            return $return;
        }));
    }

    public function testWhenMemoryLimitNotExceeded()
    {
        $middleware = new MemoryLimitMiddleware($this->logger);
        $middleware->setOptions([
            'limit' => 51
        ]);

        $this->assertEquals(true, $middleware($this->message, $this->queue, function () {
            return true;
        }));
    }

    public function testWhenMemoryLimitExceeded()
    {
        $middleware = new MemoryLimitMiddleware($this->logger);
        $middleware->setOptions([
            'limit' => 49
        ]);

        $this->assertEquals(false, $middleware($this->message, $this->queue, function () {
            return true;
        }));
    }

    public function testWhenMemoryLimitReached()
    {
        $middleware = new MemoryLimitMiddleware($this->logger);
        $middleware->setOptions([
            'limit' => 50
        ]);

        $this->assertEquals(false, $middleware($this->message, $this->queue, function () {
            return true;
        }));
    }
}

/**
 * Stub the memory_get_usage function within the current namespace
 */
function memory_get_usage()
{
    return 52428800; // 50 MB
}