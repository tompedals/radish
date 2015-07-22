<?php

namespace Radish\Producer;

use Radish\Broker\Exchange;
use Radish\Broker\Message;

class Producer implements ProducerInterface
{
    protected $exchange;

    public function __construct(Exchange $exchange)
    {
        $this->exchange = $exchange;
    }

    public function publish(Message $message)
    {
        $flags = $message->isMandatory() ? AMQP_MANDATORY : AMQP_NOPARAM;

        $this->exchange->publish($message->getBody(), $message->getRoutingKey(), $flags, $message->getAttributes());
    }
}
