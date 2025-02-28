<?php
// src/UserCache/Cache/ApcuUserCache.php

namespace App\UserCache\Cache;

use Psr\Log\LoggerInterface;

class ApcuUserCache implements UserCacheInterface
{
    private int $lifetime;

    public function __construct(
        int $lifetime)
    {
        $this->lifetime = $lifetime;
    }

    public function getUser(string $userId, callable $fallback)
    {
        $cacheKey = sprintf('user_data_%s', $userId);
        $user = apcu_fetch($cacheKey, $success);
        if ($success) {

            return $user;
        }

        $user = $fallback();
        apcu_store($cacheKey, $user, $this->lifetime);
        return $user;
    }

    public function setUser(string $userId, $user): void
    {
        $cacheKey = sprintf('user_data_%s', $userId);
        apcu_store($cacheKey, $user, $this->lifetime);
    }

    public function invalidateUser(string $userId): void
    {
        $cacheKey = sprintf('user_data_%s', $userId);
        apcu_delete($cacheKey);
    }
}
