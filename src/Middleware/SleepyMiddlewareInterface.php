<?php

namespace Radish\Middleware;

/**
 * Middleware implementing this interface will be called when sleeping between fetching messages.
 * @see https://github.com/keystonephp/queue/blob/master/src/Middleware/SleepyMiddleware.php
 */
interface SleepyMiddlewareInterface extends MiddlewareInterface
{
    /**
     * @return bool whether to terminate the consumer process
     */
    public function sleep();
}
