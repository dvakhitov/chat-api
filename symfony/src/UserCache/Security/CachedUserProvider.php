<?php
// src/UserCache/Security/CachedUserProvider.php

namespace App\UserCache\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use App\UserCache\Cache\UserCacheInterface;

class CachedUserProvider implements UserProviderInterface
{
    private EntityManagerInterface $entityManager;
    private UserCacheInterface $userCache;
    private string $userClass;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserCacheInterface $userCache,
        string $userClass
    ) {
        $this->entityManager = $entityManager;
        $this->userCache = $userCache;
        $this->userClass = $userClass;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->userCache->getUser($identifier, function () use ($identifier) {
            $user = $this->entityManager
                ->getRepository($this->userClass)
                ->find($identifier);

            if (!$user) {
                throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
            }

            return $user;
        });
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === $this->userClass || is_subclass_of($class, $this->userClass);
    }
}
