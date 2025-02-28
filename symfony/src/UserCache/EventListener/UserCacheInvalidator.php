<?php
// src/UserCache/EventListener/UserCacheInvalidator.php

namespace App\UserCache\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Entity\User;
use App\UserCache\Cache\UserCacheInterface;

class UserCacheInvalidator implements EventSubscriber
{
    private UserCacheInterface $userCache;

    public function __construct(UserCacheInterface $userCache)
    {
        $this->userCache = $userCache;
    }

    public function getSubscribedEvents(): array
    {
        return ['postUpdate', 'postRemove'];
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->invalidate($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->invalidate($args);
    }

    private function invalidate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject(); // новый метод вместо getEntity()
        if ($entity instanceof User) {
            $this->userCache->invalidateUser($entity->getId());
        }
    }
}
