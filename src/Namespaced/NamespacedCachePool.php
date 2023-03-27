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

namespace Cache\Namespaced;

use Cache\Hierarchy\HierarchicalPoolInterface;
use Psr\Cache\CacheItemInterface;

/**
 * Prefix all the stored items with a namespace. Also make sure you can clear all items
 * in that namespace.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class NamespacedCachePool implements HierarchicalPoolInterface
{
    private HierarchicalPoolInterface $cachePool;

    private string $namespace;

    public function __construct(HierarchicalPoolInterface $cachePool, string $namespace)
    {
        $this->cachePool = $cachePool;
        $this->namespace = $namespace;
    }

    /**
     * Add namespace prefix on the key.
     */
    private function prefixValue(string &$key): static
    {
        // |namespace|key
        $key = HierarchicalPoolInterface::HIERARCHY_SEPARATOR
            .$this->namespace
            .HierarchicalPoolInterface::HIERARCHY_SEPARATOR
            .$key;

        return $this;
    }

    /**
     * @param string[] $keys
     */
    private function prefixValues(array &$keys): static
    {
        foreach ($keys as &$key) {
            $this->prefixValue($key);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        return $this
            ->prefixValue($key)
            ->cachePool
            ->getItem($key);
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-return iterable<string, \Psr\Cache\CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        return $this
            ->prefixValues($keys)
            ->cachePool
            ->getItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        return $this
            ->prefixValue($key)
            ->cachePool
            ->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this
            ->cachePool
            ->deleteItem(HierarchicalPoolInterface::HIERARCHY_SEPARATOR.$this->namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        return $this
            ->prefixValue($key)
            ->cachePool
            ->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        return $this
            ->prefixValues($keys)
            ->cachePool
            ->deleteItems($keys);
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
