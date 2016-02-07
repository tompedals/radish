<?php

namespace Radish\Producer;

use Mockery;

class BlackHoleProducerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $factory = new BlackHoleProducerFactory();
        $this->assertInstanceOf('Radish\Producer\BlackHoleProducer', $factory->create('test'));
    }
}
