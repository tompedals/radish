<?php

namespace Radish\Producer;

use Radish\Broker\Message;

interface ProducerInterface
{
    public function publish(Message $message);
}
