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

namespace Cache\Prefixed;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Prefix all cache items with a string.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class PrefixedCachePool implements CacheItemPoolInterface
{
    use PrefixedUtilityTrait;

    private CacheItemPoolInterface $cachePool;

    public function __construct(CacheItemPoolInterface $cachePool, string $prefix)
    {
        $this->cachePool = $cachePool;
        $this->prefix    = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        $this->prefixValue($key);

        return $this->cachePool->getItem($key);
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-param array<string> $keys
     *
     * @phpstan-return array<string, \Psr\Cache\CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        $this->prefixValues($keys);

        return $this->cachePool->getItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        $this->prefixValue($key);

        return $this->cachePool->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->cachePool->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        $this->prefixValue($key);

        return $this->cachePool->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $this->prefixValues($keys);

        return $this->cachePool->deleteItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->cachePool->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->cachePool->saveDeferred($item);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        return $this->cachePool->commit();
    }
}
