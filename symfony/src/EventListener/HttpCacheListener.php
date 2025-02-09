<?php

namespace App\EventListener;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpCacheListener
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    // Проверяем кэш перед обработкой запроса
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->isCacheableRequest($request)) {
            return;
        }

        $cacheKey = $this->getCacheKey($request);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $response = new Response($cacheItem->get());
            $response->headers->set('X-Cache', 'HIT'); // Индикатор кэшированного ответа
            $event->setResponse($response);
        }
    }

    // Кэшируем ответ после обработки запроса
    public function onKernelResponse(ResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$this->isCacheableRequest($request) || !$response->isSuccessful()) {
            return;
        }

        $cacheKey = $this->getCacheKey($request);
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->set($response->getContent());
        $cacheItem->expiresAfter(3600); // Кэшируем на 1 час

        $this->cache->save($cacheItem);
    }

    // Проверяем, нужно ли кэшировать этот запрос
    private function isCacheableRequest(Request $request): bool
    {
        return $request->isMethod('GET'); // Кэшируем только GET-запросы
    }

    // Генерируем ключ для кэша
    private function getCacheKey(Request $request): string
    {
        return 'http_cache_' . md5($request->getUri());
    }
}
