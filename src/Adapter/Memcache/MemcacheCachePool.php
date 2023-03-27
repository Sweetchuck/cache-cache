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

namespace Cache\Adapter\Memcache;

use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;
use Cache\Adapter\Common\TagSupportWithArray;

class MemcacheCachePool extends AbstractCachePool
{
    use TagSupportWithArray;

    protected \Memcache $cache;

    public function __construct(\Memcache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchObjectFromCache(string $key): array
    {
        $emptyValue = [false, null, [], null];
        $entry = $this->cache->get($key);
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
        $this->cache->delete($key);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function storeItemInCache(PhpCacheItem $item, ?int $ttl): bool
    {
        $data = serialize([true, $item->get(), $item->getTags(), $item->getExpirationTimestamp()]);

        return $this->cache->set($item->getKey(), $data, 0, $ttl ?: 0);
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
