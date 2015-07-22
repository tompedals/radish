<?php

namespace Radish\Broker;

interface ConsumableInterface
{
    public function consume(callable $callback = null);
    public function cancel();
}
