<?php

namespace Radish\Consumer;

use Psr\Log\LoggerInterface;
use Radish\Broker\QueueLoader;
use Radish\Middleware\MiddlewareLoader;

class PollerFactory
{
    /**
     * @var QueueLoader
     */
    protected $queueLoader;
    /**
     * @var MiddlewareLoader
     */
    private $middlewareLoader;
    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @param QueueLoader $queueLoader
     * @param MiddlewareLoader $middlewareLoader
     * @param LoggerInterface|null $logger
     */
    public function __construct(QueueLoader $queueLoader, MiddlewareLoader $middlewareLoader, LoggerInterface $logger = null)
    {
        $this->queueLoader = $queueLoader;
        $this->middlewareLoader = $middlewareLoader;
        $this->logger = $logger;
    }

    /**
     * @param array $queueNames
     * @param array $middlewareOptions
     * @param array $workers
     * @param int $interval
     * @return Poller
     */
    public function create(array $queueNames, array $middlewareOptions, array $workers, $interval)
    {
        return new Poller(
            $this->queueLoader->load($queueNames),
            $this->middlewareLoader->load($middlewareOptions),
            $workers,
            $interval,
            $this->logger
        );
    }
}
