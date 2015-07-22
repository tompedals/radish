<?php

namespace Radish\Broker;

use AMQPChannel;
use AMQPConnection;
use AMQPExchange;
use AMQPQueue;

class AMQPFactory
{
    public function createConnection(array $credentials)
    {
        return new AMQPConnection($credentials);
    }

    public function createChannel(AMQPConnection $connection)
    {
        return new AMQPChannel($connection);
    }

    public function createExchange(AMQPChannel $channel)
    {
        return new AMQPExchange($channel);
    }

    public function createQueue(AMQPChannel $channel)
    {
        return new AMQPQueue($channel);
    }
}
