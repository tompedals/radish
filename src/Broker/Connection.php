<?php

namespace Radish\Broker;

use AMQPChannel;
use AMQPConnection;

class Connection
{
    /**
     * @var AMQPFactory
     */
    protected $factory;

    /**
     * The connection credentials
     * @var array
     */
    protected $credentials;

    /**
     * The internal connection
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * The internal channel
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * Whether the connection has been connected.
     * @var bool
     */
    protected $connected = false;

    public function __construct(AMQPFactory $factory, array $credentials)
    {
        $this->factory = $factory;
        $this->credentials = $credentials;
    }

    public function __deconstruct()
    {
        if ($this->isConnected()) {
            $this->connection->disconnect();
        }
    }

    public function connect()
    {
        if (!$this->isConnected()) {
            $this->connection = $this->factory->createConnection($this->credentials);
            $this->connection->connect();

            // Mark as connected
            $this->connected = true;
        }
    }

    public function isConnected()
    {
        return $this->connected;
    }

    public function getChannel()
    {
        if ($this->channel === null) {
            $this->connect();
            $this->channel = $this->factory->createChannel($this->connection);
        }

        return $this->channel;
    }

    public function createExchange()
    {
        return $this->factory->createExchange($this->getChannel());
    }

    public function createQueue()
    {
        return $this->factory->createQueue($this->getChannel());
    }
}
