<?php

declare(strict_types = 1);

/**
 * @file
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm
 *     <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\Adapter\Redis;

use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;
use Cache\Hierarchy\HierarchicalCachePoolTrait;
use Cache\Hierarchy\HierarchicalPoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RedisCachePool extends AbstractCachePool implements HierarchicalPoolInterface
{
    use HierarchicalCachePoolTrait;

    protected \Redis|\RedisArray|\RedisCluster $cache;

    public function __construct(\Redis|\RedisArray|\RedisCluster $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RedisException
     */
    protected function fetchObjectFromCache(string $key): array
    {
        $emptyValue = [false, null, [], null];
        $entry = $this->cache->get($this->getHierarchyKey($key));
        if (!$entry) {
            return $emptyValue;
        }

        $result = is_string($entry) ?
            unserialize($entry)
            : false;

        return false === $result ?
            $emptyValue
            : $result;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RedisException
     */
    protected function clearAllObjectsFromCache(): bool
    {
        if ($this->cache instanceof \RedisCluster) {
            return $this->clearAllObjectsFromCacheCluster();
        }

        /** @var bool|\Redis $result */
        $result = $this->cache->flushDb();
        if (is_bool($result)) {
            return $result;
        }

        // At this point $result is \Redis.
        // @todo Check what to do in multimode.
        return true;
    }

    /**
     * Clear all objects from all nodes in the cluster.
     *
     * @throws \RedisException
     */
    protected function clearAllObjectsFromCacheCluster(): bool
    {
        if (!($this->cache instanceof \RedisCluster)) {
            return false;
        }

        foreach ($this->cache->_masters() as $node) {
            if (!$this->cache->flushDB($node)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RedisException
     */
    protected function clearOneObjectFromCache(string $key): bool
    {
        $path = null;
        $keyString = $this->getHierarchyKey($key, $path);
        if ($path) {
            $this->cache->incr($path);
        }
        $this->clearHierarchyKeyCache();

        return $this->cache->del($keyString) >= 0;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RedisException
     */
    protected function storeItemInCache(PhpCacheItem $item, ?int $ttl): bool
    {
        $key = $this->getHierarchyKey($item->getKey());
        $data = serialize([
            true,
            $item->get(),
            $item->getTags(),
            $item->getExpirationTimestamp(),
        ]);

        return $ttl ?
            $this->cache->setex($key, $ttl, $data)
            : $this->cache->set($key, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RedisException
     */
    protected function getDirectValue(string $name): mixed
    {
        return $this->cache->get($name);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RedisException
     */
    protected function appendListItem(string $name, string $key)
    {
        $this->cache->lPush($name, $key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RedisException
     */
    protected function getList(string $name): array
    {
        return $this->cache->lRange($name, 0, -1);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RedisException
     */
    protected function removeList(string $name): bool
    {
        return $this->cache->del($name) >= 0;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RedisException
     */
    protected function removeListItem(string $name, string $key)
    {
        $this->cache->lrem($name, $key, 0);
    }
}
