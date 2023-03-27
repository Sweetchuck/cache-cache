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

namespace Cache\Adapter\Memcached;

use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;
use Cache\Adapter\Common\TagSupportWithArray;
use Cache\Hierarchy\HierarchicalCachePoolTrait;
use Cache\Hierarchy\HierarchicalPoolInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class MemcachedCachePool extends AbstractCachePool implements HierarchicalPoolInterface
{
    use HierarchicalCachePoolTrait;
    use TagSupportWithArray;

    protected \Memcached $cache;

    public function __construct(\Memcached $cache)
    {
        $this->cache = $cache;
        $this->cache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchObjectFromCache(string $key): array
    {
        $emptyValue = [false, null, [], null];
        $entry = $this->cache->get($this->getHierarchyKey($key));
        if (!$entry) {
            return $emptyValue;
        }

        return unserialize($entry) ?: $emptyValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function clearAllObjectsFromCache(): bool
    {
        return $this->cache->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function clearOneObjectFromCache(string $key): bool
    {
        $this->commit();
        $path = null;
        $keyString = $this->getHierarchyKey($key, $path);
        if ($path) {
            $this->cache->increment($path);
        }
        $this->clearHierarchyKeyCache();

        // Return true if key not found.
        return $this->cache->delete($keyString)
            || $this->cache->getResultCode() === \Memcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    protected function storeItemInCache(PhpCacheItem $item, ?int $ttl): bool
    {
        if ($ttl === null) {
            $ttl = 0;
        }

        if ($ttl < 0) {
            return false;
        }

        if ($ttl > 86400 * 30) {
            // Any time higher than 30 days is interpreted as a unix timestamp date.
            // https://github.com/memcached/memcached/wiki/Programming#expiration
            $ttl = time() + $ttl;
        }

        $key = $this->getHierarchyKey($item->getKey());

        return $this->cache->set(
            $key,
            serialize([
                true,
                $item->get(),
                $item->getTags(),
                $item->getExpirationTimestamp()
            ]),
            $ttl,
        );
    }

    protected function preRemoveItem(string $key): static
    {
        parent::preRemoveItem($key);
        if (!$this->isHierarchyKey($key)) {
            return $this;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectValue(string $name): mixed
    {
        return $this->cache->get($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function setDirectValue(string $name, mixed $value)
    {
        $this->cache->set($name, $value);
    }
}
