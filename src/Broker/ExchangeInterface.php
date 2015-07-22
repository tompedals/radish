<?php

namespace Radish\Broker;

interface ExchangeInterface
{
    public function getName();
    public function getType();
    public function isDurable();
    public function declareExchange();
    public function publish($body, $routingKey, $flags, array $attributes);
}
