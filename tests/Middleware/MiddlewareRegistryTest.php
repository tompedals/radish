<?php

namespace Radish\Middleware;

use Mockery;

class MiddlewareRegistryTest extends \PHPUnit_Framework_TestCase
{
    public $container;
    public $registry;

    public function setUp()
    {
        $this->container = Mockery::mock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->registry = new MiddlewareRegistry($this->container);
    }

    public function testGet()
    {
        $middleware = Mockery::mock('Radish\Middleware\MiddlewareInterface');

        $this->container->shouldReceive('has')
            ->with('service_name')
            ->andReturn(true);

        $this->container->shouldReceive('get')
            ->with('service_name')
            ->andReturn($middleware);

        $this->registry->register('middleware_name', 'service_name');

        $this->assertSame($middleware, $this->registry->get('middleware_name'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetThrowsExceptionWhenMiddlewareNotRegistered()
    {
        $middleware = Mockery::mock('Radish\Middleware\MiddlewareInterface');

        $this->assertSame($middleware, $this->registry->get('middleware_name'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetThrowsExceptionWhenMiddlewareServiceNotDefined()
    {
        $middleware = Mockery::mock('Radish\Middleware\MiddlewareInterface');

        $this->container->shouldReceive('has')
            ->andReturn(false);

        $this->registry->register('middleware_name', 'service_name');

        $this->assertSame($middleware, $this->registry->get('middleware_name'));
    }
}
