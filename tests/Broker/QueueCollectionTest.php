<?php

namespace Radish\Broker;

use Mockery;
use Mockery\Mock;
use PHPUnit_Framework_TestCase;

class QueueCollectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var QueueCollection
     */
    private $collection;
    /**
     * @var Mock|Queue
     */
    private $queue1;
    /**
     * @var Mock|Queue
     */
    private $queue2;

    public function setUp()
    {
        $this->collection = new QueueCollection();
        $this->queue1 = Mockery::mock(Queue::class, [
            'getName' => 'a',
            'pop' => Mockery::mock(Message::class)
        ]);
        $this->queue2 = Mockery::mock(Queue::class, [
            'getName' => 'b',
            'pop' => Mockery::mock(Message::class)
        ]);
    }

    public function testPopReturnsMessage()
    {
        $this->collection->add($this->queue1);
        $this->collection->add($this->queue2);

        $this->queue1->shouldReceive('pop')
            ->once()
            ->andReturn(Mockery::mock(Message::class));

        $this->queue2->shouldReceive('pop')
            ->never();

        $message = $this->collection->pop();

        static::assertInstanceOf(Message::class, $message);
    }

    public function testPopPopsEachQueueInOrder()
    {
        $this->collection->add($this->queue1);
        $this->collection->add($this->queue2);

        $message1 = Mockery::mock(Message::class);
        $message2 = Mockery::mock(Message::class);

        $this->queue1->shouldReceive('pop')
            ->once()
            ->andReturn($message1);

        $this->queue2->shouldReceive('pop')
            ->once()
            ->andReturn($message2);

        static::assertSame($message1, $this->collection->pop());
        static::assertSame($message2, $this->collection->pop());
    }

    public function testPopReturnsNullWhenNoMoreMessages()
    {
        $this->collection->add($this->queue1);

        $message1 = Mockery::mock(Message::class);

        $this->queue1->shouldReceive('pop')
            ->twice()
            ->andReturn($message1, null);

        static::assertSame($message1, $this->collection->pop());
        static::assertNull($this->collection->pop());
    }
}
