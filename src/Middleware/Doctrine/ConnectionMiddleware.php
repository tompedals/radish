<?php

namespace Radish\Middleware\Doctrine;

use Doctrine\DBAL\DBALException;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\MiddlewareInterface;

class ConnectionMiddleware implements MiddlewareInterface
{
    protected $connections;

    public function __construct($connections)
    {
        $this->connections = $connections;
    }

    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        // Ping each connection to ensure it's not timed out
        foreach ($this->connections as $connection) {
            if ($connection->isConnected()) {
                try {
                    $connection->query($connection->getDatabasePlatform()->getDummySelectSQL());
                } catch (DBALException $e) {
                    // Closed timed out connections
                    $connection->close();
                }
            }
        }

        return $next($message, $queue);
    }
}
