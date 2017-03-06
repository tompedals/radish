<?php

namespace Radish\Consumer;

use Radish\Broker\QueueCollection;
use Radish\Broker\QueueLoader;
use Radish\Broker\QueueRegistry;
use Radish\Middleware\ConfigurableInterface;
use Radish\Middleware\MiddlewareLoader;
use Radish\Middleware\MiddlewareRegistry;
use Psr\Log\LoggerInterface;
use Radish\Poller\Poller;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsumerFactory implements ConsumerFactoryInterface
{
    /**
     * @var LoggerInterface|null
     */
    protected $logger;
    /**
     * @var MiddlewareLoader
     */
    private $middlewareLoader;
    /**
     * @var QueueLoader
     */
    private $queueLoader;

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
     * @return Consumer
     */
    public function create(array $queueNames, array $middlewareOptions, array $workers)
    {
        return new Consumer(
            $this->queueLoader->load($queueNames),
            $this->middlewareLoader->load($middlewareOptions),
            $workers,
            $this->logger
        );
    }
}
