<?php

namespace Radish\Middleware;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface ConfigurableInterface
{
    public function configureOptions(OptionsResolver $resolver);
    public function setOptions(array $options);
}
