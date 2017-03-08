<?php

namespace Radish\Broker;

use Mockery;
use Mockery\Mock;
use PHPUnit_Framework_TestCase;

class QueueLoaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mock|QueueRegistry
     */
    private $queueRegistry;
    /**
     * @var QueueLoader
     */
    private $loader;

    public function setUp()
    {
        $this->queueRegistry = Mockery::mock('Radish\Broker\QueueRegistry', [
            'get' => Mockery::mock('Radish\Broker\Queue', [
                'getName' => 'a',
            ]),
        ]);
        $this->loader = new QueueLoader($this->queueRegistry);
    }

    public function testLoadGetsEachQueueFromRegistry()
    {
        $this->queueRegistry->shouldReceive('get')
            ->with('a')
            ->times(1)
            ->andReturn(Mockery::mock('Radish\Broker\Queue', [
                'getName' => 'a',
            ]));

        $this->queueRegistry->shouldReceive('get')
            ->with('b')
            ->times(1)
            ->andReturn(Mockery::mock('Radish\Broker\Queue', [
                'getName' => 'a',
            ]));

        $this->loader->load(['a', 'b']);
    }

    public function testLoadReturnsQueueCollection()
    {
        $collection = $this->loader->load(['a']);

        static::assertInstanceOf('Radish\Broker\QueueCollection', $collection);
    }
}
