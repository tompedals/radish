<?php

namespace Radish\Middleware\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Radish\Broker\Message;
use Radish\Broker\Queue;
use Radish\Middleware\MiddlewareInterface;

class ObjectManagerMiddleware implements MiddlewareInterface
{
    protected $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(Message $message, Queue $queue, callable $next)
    {
        $return = $next($message, $queue);

        foreach ($this->managerRegistry->getManagers() as $managerName => $manager) {
            if (!$manager->isOpen()) {
                $this->managerRegistry->resetManager($managerName);
            } else {
                $manager->clear();
            }
        }

        return $return;
    }
}
