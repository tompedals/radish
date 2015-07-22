<?php

namespace Radish\Consumer;

use Mockery;

class ConsumerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public $queueRegistry;
    public $middlewareRegistry;
    public $factory;

    public function setUp()
    {
        $this->queueRegistry = Mockery::mock('Radish\Broker\QueueRegistry');
        $this->middlewareRegistry = Mockery::mock('Radish\Middleware\MiddlewareRegistry');

        $this->factory = new ConsumerFactory($this->queueRegistry, $this->middlewareRegistry);
    }

    public function testCreate()
    {
        $queueNames = ['test_queue'];

        $queue = Mockery::mock('Radish\Broker\Queue', [
            'getName' => 'test_queue'
        ]);

        $this->queueRegistry->shouldReceive('get')
            ->with('test_queue')
            ->andReturn($queue)
            ->once();

        $middlewareOptions = [];

        $consumer = $this->factory->create($queueNames, $middlewareOptions, []);
    }

    public function testCreateWithConfigurableMiddleware()
    {
        $queueNames = ['test_queue'];

        $queue = Mockery::mock('Radish\Broker\Queue', [
            'getName' => 'test_queue'
        ]);

        $this->queueRegistry->shouldReceive('get')
            ->with('test_queue')
            ->andReturn($queue)
            ->once();

        $middlewareOptions = [
            'max_messages' => [
                'limit' => 10
            ]
        ];

        $middleware = Mockery::mock('Radish\Middleware\ConfigurableInterface');

        $middleware->shouldReceive('configureOptions')
            ->andReturnUsing(function ($resolver) {
                $resolver->setDefaults([
                    'limit' => 5
                ]);
            })
            ->once();

        $middleware->shouldReceive('setOptions')
            ->with($middlewareOptions['max_messages'])
            ->once();

        $this->middlewareRegistry->shouldReceive('get')
            ->with('max_messages')
            ->andReturn($middleware)
            ->once();

        $consumer = $this->factory->create($queueNames, $middlewareOptions, []);

        $this->assertInstanceOf('Radish\Consumer\ConsumerInterface', $consumer);
    }
}
