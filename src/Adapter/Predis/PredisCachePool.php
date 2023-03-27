<?php

declare(strict_types = 1);

/**
 * @file
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\Adapter\Predis;

use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;
use Cache\Hierarchy\HierarchicalCachePoolTrait;
use Cache\Hierarchy\HierarchicalPoolInterface;
use Predis\ClientInterface as Client;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class PredisCachePool extends AbstractCachePool implements HierarchicalPoolInterface
{
    use HierarchicalCachePoolTrait;

    protected \Predis\ClientInterface $cache;

    public function __construct(Client $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchObjectFromCache(string $key): array
    {
        $raw = $this->cache->get($this->getHierarchyKey($key));
        if (null === $raw) {
            return [false, null, [], null];
        }

        $result = unserialize($raw);

        return false === $result ?
            [false, null, [], null]
            : $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function clearAllObjectsFromCache(): bool
    {
        return 'OK' === $this->cache->flushdb()->getPayload();
    }

    /**
     * {@inheritdoc}
     */
    protected function clearOneObjectFromCache(string $key): bool
    {
        $path      = null;
        $keyString = $this->getHierarchyKey($key, $path);
        if ($path) {
            $this->cache->incr($path);
        }
        $this->clearHierarchyKeyCache();

        return $this->cache->del($keyString) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function storeItemInCache(PhpCacheItem $item, ?int $ttl): bool
    {
        if ($ttl < 0) {
            return false;
        }

        $key  = $this->getHierarchyKey($item->getKey());
        $data = serialize([true, $item->get(), $item->getTags(), $item->getExpirationTimestamp()]);

        if ($ttl === null || $ttl === 0) {
            return 'OK' === $this->cache->set($key, $data)->getPayload();
        }

        return 'OK' === $this->cache->setex($key, $ttl, $data)->getPayload();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDirectValue(string $key): mixed
    {
        return $this->cache->get($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function appendListItem(string $name, string $key)
    {
        $this->cache->lpush($name, [$key]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getList(string $name): array
    {
        return $this->cache->lrange($name, 0, -1);
    }

    /**
     * {@inheritdoc}
     */
    protected function removeList(string $name): bool
    {
        return $this->cache->del($name) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function removeListItem(string $name, string $key)
    {
        $this->cache->lrem($name, 0, $key);
    }
}
