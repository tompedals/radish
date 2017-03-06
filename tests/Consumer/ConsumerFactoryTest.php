<?php

namespace Radish\Consumer;

use Mockery;
use Mockery\Mock;
use Radish\Broker\QueueLoader;
use Radish\Middleware\MiddlewareLoader;

class ConsumerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Mock|QueueLoader
     */
    public $queueLoader;
    /**
     * @var Mock|MiddlewareLoader
     */
    public $middlewareLoader;
    /**
     * @var ConsumerFactory
     */
    public $factory;

    public function setUp()
    {
        $this->queueLoader = Mockery::mock('Radish\Broker\QueueLoader');
        $this->middlewareLoader = Mockery::mock('Radish\Middleware\MiddlewareLoader');

        $this->factory = new ConsumerFactory($this->queueLoader, $this->middlewareLoader);
    }

    public function testCreate()
    {
        $queueNames = ['test_queue'];
        $middlewareOptions = ['options'];

        $this->queueLoader->shouldReceive('load')
            ->with($queueNames)
            ->once()
            ->andReturn(Mockery::mock('Radish\Broker\QueueCollection'));

        $this->middlewareLoader->shouldReceive('load')
            ->with($middlewareOptions)
            ->once()
            ->andReturn([Mockery::mock('Radish\Middleware\MiddlewareInterface')]);

        $consumer = $this->factory->create($queueNames, $middlewareOptions, []);

        $this->assertInstanceOf('Radish\Consumer\Consumer', $consumer);
    }
}
