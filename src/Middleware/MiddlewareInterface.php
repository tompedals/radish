<?php

namespace Radish\Middleware;

use Radish\Broker\Message;
use Radish\Broker\Queue;

interface MiddlewareInterface
{
    public function __invoke(Message $message, Queue $queue, callable $next);
}
