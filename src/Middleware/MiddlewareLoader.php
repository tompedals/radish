<?php

namespace Radish\Middleware;

use Symfony\Component\OptionsResolver\OptionsResolver;

class MiddlewareLoader
{
    /**
     * @var MiddlewareRegistry
     */
    private $middlewareRegistry;

    /**
     * @param MiddlewareRegistry $middlewareRegistry
     */
    public function __construct(MiddlewareRegistry $middlewareRegistry)
    {
        $this->middlewareRegistry = $middlewareRegistry;
    }

    /**
     * @param array $middlewareOptions
     * @return MiddlewareInterface[]
     */
    public function load(array $middlewareOptions)
    {
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
        return $middlewares;
    }
}
