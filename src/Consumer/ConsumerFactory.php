<?php

namespace Radish\Consumer;

use Radish\Broker\QueueCollection;
use Radish\Broker\QueueRegistry;
use Radish\Middleware\ConfigurableInterface;
use Radish\Middleware\MiddlewareRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsumerFactory implements ConsumerFactoryInterface
{
    protected $queueRegistry;
    protected $middlewareRegistry;
    protected $logger;

    public function __construct(QueueRegistry $queueRegistry, MiddlewareRegistry $middlewareRegistry, LoggerInterface $logger = null)
    {
        $this->queueRegistry = $queueRegistry;
        $this->middlewareRegistry = $middlewareRegistry;
        $this->logger = $logger;
    }

    public function create(array $queueNames, array $middlewareOptions, array $workers)
    {
        $queues = new QueueCollection();
        foreach ($queueNames as $queueName) {
            $queues->add($this->queueRegistry->get($queueName));
        }

        $middlewares = [];
        foreach ($middlewareOptions as $middlewareName => $options) {
            $middleware = $this->middlewareRegistry->get($middlewareName);
            if ($middleware instanceof ConfigurableInterface) {
                if (!is_array($options)) {
                    $options = [];
                }

                $optionsResolver = new OptionsResolver();
                $middleware->configureOptions($optionsResolver);
                $middleware->setOptions($optionsResolver->resolve($options));
            }

            $middlewares[] = $middleware;
        }

        return new Consumer($queues, $middlewares, $workers, $this->logger);
    }
}
