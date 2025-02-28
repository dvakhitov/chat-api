<?php
// src/UserCache/Cache/UserCacheInterface.php

namespace App\UserCache\Cache;

interface UserCacheInterface
{
    /**
     * Возвращает данные пользователя из кэша, либо, если отсутствуют, вызывает $fallback,
     * сохраняет результат и возвращает его.
     *
     * @param string   $userId   Идентификатор пользователя
     * @param callable $fallback Функция для получения данных (например, из БД)
     *
     * @return mixed
     */
    public function getUser(string $userId, callable $fallback);

    /**
     * Сохраняет данные пользователя в кэш.
     *
     * @param string $userId Идентификатор пользователя
     * @param mixed  $user   Данные пользователя
     */
    public function setUser(string $userId, $user): void;

    /**
     * Инвалидирует (удаляет) данные пользователя из кэша.
     *
     * @param string $userId Идентификатор пользователя
     */
    public function invalidateUser(string $userId): void;
}
