<?php

namespace Radish\Broker;

interface QueueInterface extends ConsumableInterface
{
    public function declareQueue();
    public function bind();
    public function ack(Message $message);
    public function nack(Message $message, $requeue = false);
}
