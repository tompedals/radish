<?php

namespace Radish\Middleware;

use Symfony\Component\DependencyInjection\ContainerInterface;

class MiddlewareRegistry
{
    protected $container;
    protected $middlewares = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register($name, $serviceId)
    {
        $this->middlewares[$name] = $serviceId;
    }

    public function get($name)
    {
        if (!isset($this->middlewares[$name])) {
            throw new \RuntimeException(sprintf('Unknown middleware "%s"', $name));
        }

        $serviceId = $this->middlewares[$name];
        if (!$this->container->has($serviceId)) {
            throw new \RuntimeException(sprintf('Service "%s" for middleware "%s" does not exist', $serviceId, $name));
        }

        return $this->container->get($serviceId);
    }
}
