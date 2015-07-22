<?php

namespace Radish\Producer;

use Radish\Broker\Message;

class BlackHoleProducer implements ProducerInterface
{
    public function publish(Message $message)
    {
        // Into the abyss
    }
}
