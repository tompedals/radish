<?php

namespace Radish\Producer;

interface ProducerFactoryInterface
{
    public function create($exchangeName);
}
