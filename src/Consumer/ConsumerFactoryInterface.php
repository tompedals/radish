<?php

namespace Radish\Consumer;

interface ConsumerFactoryInterface
{
    public function create(array $queueNames, array $middlewareOptions, array $workers);
}
