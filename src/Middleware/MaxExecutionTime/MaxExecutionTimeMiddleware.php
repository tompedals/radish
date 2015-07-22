<?php

namespace Radish\Middleware\MaxExecutionTime;

use Psr\Log\LoggerInterface;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\ConfigurableInterface;
use Radish\Middleware\InitializableInterface;
use Radish\Middleware\MiddlewareInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaxExecutionTimeMiddleware implements MiddlewareInterface, ConfigurableInterface, InitializableInterface
{
    protected $logger;
    protected $startTime;
    protected $maxExecutionTime;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function initialize()
    {
        $this->startTime = microtime(true);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'limit' => 86400
        ]);

        $resolver->setAllowedTypes('limit', 'int');
    }

    public function setOptions(array $options)
    {
        $this->maxExecutionTime = $options['limit'];
    }

    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        $result = $next($message, $queue);

        if ($this->isTimeExceeded()) {
            if ($this->logger) {
                $this->logger->info(sprintf('Reached maximum execution time of %d seconds', $this->maxExecutionTime));
            }

            return false;
        }

        return $result;
    }

    private function isTimeExceeded()
    {
        $runningTime = microtime(true) - $this->startTime;

        return $runningTime > $this->maxExecutionTime;
    }
}
