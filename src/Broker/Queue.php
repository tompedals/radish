<?php

namespace Radish\Broker;

use AMQPEnvelope;
use AMQPQueue;

class Queue implements QueueInterface
{
    protected $queue;
    protected $connection;
    protected $name;
    protected $durable;
    protected $bindings;

    /**
     * The exchange to republish messages to when they expire, useful for retrying.
     *
     * @var string
     */
    protected $deadLetterExchange;

    /**
     * @var int
     */
    protected $maxPriority;

    public function __construct(Connection $connection, $name, $durable = true, array $bindings)
    {
        $this->connection = $connection;
        $this->name = $name;
        $this->durable = $durable;
        $this->bindings = $bindings;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isDurable()
    {
        return $this->durable;
    }

    public function getDeadLetterExchange()
    {
        return $this->deadLetterExchange;
    }

    public function setDeadLetterExchange($deadLetterExchange)
    {
        $this->deadLetterExchange = $deadLetterExchange;
    }

    public function getMaxPriority()
    {
        return $this->maxPriority;
    }

    public function setMaxPriority($maxPriority)
    {
        $this->maxPriority = $maxPriority;
    }

    public function declareQueue()
    {
        $this->getAMQPQueue()->declareQueue();
    }

    public function bind()
    {
        foreach ($this->bindings as $binding) {
            $this->getAMQPQueue()->bind(
                $binding->getExchangeName(),
                $binding->getRoutingKey(),
                $binding->getArguments()
            );
        }
    }

    public function consume(callable $callback = null)
    {
        if ($callback !== null) {
            // Backup the original callback for use within the closure
            $originalCallback = $callback;

            $callback = function (AMQPEnvelope $envelope) use ($originalCallback) {
                // Convert the internal envelope object to message
                return $originalCallback(Message::createFromEnvelope($envelope));
            };
        }

        $this->getAMQPQueue()->consume($callback);
    }

    /**
     * @return Message|null
     */
    public function pop()
    {
        $envelope = $this->getAMQPQueue()->get();

        if ($envelope instanceof AMQPEnvelope) {
            return Message::createFromEnvelope($envelope);
        }

        return null;
    }

    public function cancel()
    {
        $this->getAMQPQueue()->cancel();
    }

    public function ack(Message $message)
    {
        $this->getAMQPQueue()->ack($message->getDeliveryTag());
    }

    public function nack(Message $message, $requeue = false)
    {
        $this->getAMQPQueue()->nack($message->getDeliveryTag(), $requeue ? AMQP_REQUEUE : AMQP_NOPARAM);
    }

    private function getAMQPQueue()
    {
        if ($this->queue === null) {
            // Lazyily connects and creates the channel
            $this->queue = $this->connection->createQueue();
            $this->queue->setName($this->name);
            $this->queue->setFlags($this->durable ? AMQP_DURABLE : AMQP_NOPARAM);

            if ($this->getDeadLetterExchange() !== null) {
                $this->queue->setArgument('x-dead-letter-exchange', $this->getDeadLetterExchange());
            }

            if ($this->getMaxPriority() !== null) {
                $this->queue->setArgument('x-max-priority', $this->getMaxPriority());
            }
        }

        return $this->queue;
    }
}
