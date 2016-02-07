<?php

namespace Radish\Producer;

use Radish\Broker\Message;

class BlackHoleProducer implements ProducerInterface
{
    /**
     * @codeCoverageIgnore
     */
    public function publish(Message $message)
    {
        // Into the abyss
    }
}
