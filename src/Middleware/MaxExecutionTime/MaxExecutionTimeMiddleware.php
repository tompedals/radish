<?php

namespace Radish\Middleware\MaxExecutionTime;

use Psr\Log\LoggerInterface;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\Ack\SleepyMiddleware;
use Radish\Middleware\ConfigurableInterface;
use Radish\Middleware\InitializableInterface;
use Radish\Middleware\MiddlewareInterface;
use Radish\Middleware\SleepyMiddlewareInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaxExecutionTimeMiddleware implements
    MiddlewareInterface,
    ConfigurableInterface,
    InitializableInterface,
    SleepyMiddlewareInterface
{
    protected $logger;
    protected $startTime;
    protected $maxExecutionTime;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $this->startTime = microtime(true);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'limit' => 86400
        ]);

        $resolver->setAllowedTypes('limit', 'int');
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->maxExecutionTime = $options['limit'];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        $result = $next($message, $queue);

        if ($this->isTimeExceeded()) {
            return false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function sleep()
    {
        if ($this->isTimeExceeded()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function isTimeExceeded()
    {
        $runningTime = microtime(true) - $this->startTime;

        if ($runningTime > $this->maxExecutionTime) {
            $this->logger->info(sprintf('Reached maximum execution time of %d seconds', $this->maxExecutionTime));

            return true;
        }

        return false;
    }
}
