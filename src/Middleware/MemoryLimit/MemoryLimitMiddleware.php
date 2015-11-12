<?php

namespace Radish\Middleware\MemoryLimit;

use Psr\Log\LoggerInterface;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\ConfigurableInterface;
use Radish\Middleware\InitializableInterface;
use Radish\Middleware\MiddlewareInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MemoryLimitMiddleware implements MiddlewareInterface, ConfigurableInterface
{
    protected $logger;

    /**
     * The memory limit in MB
     *
     * @var int
     */
    protected $memoryLimit;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'limit' => null
        ]);

        $resolver->setAllowedTypes('limit', 'int');
    }

    public function setOptions(array $options)
    {
        $this->memoryLimit = $options['limit'];
    }

    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        $result = $next($message, $queue);

        if ($this->exceededMemoryLimit()) {
            if ($this->logger) {
                $this->logger->info(sprintf('Reached memory limit (%d MB)', $this->memoryLimit));
            }

            return false;
        }

        return $result;
    }

    /**
     * Assert whether the memory limit has been exceeded
     *
     * @return bool
     */
    protected function exceededMemoryLimit()
    {
        if ($this->memoryLimit === null) {
            return false;
        }

        // Convert the limit from MB to bytes and compare with current usage (that is returned in bytes)
        return memory_get_usage() >= $this->memoryLimit * 1024 * 1024;
    }
}
