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

namespace Cache\Adapter\Void;

use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;
use Cache\Hierarchy\HierarchicalPoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class VoidCachePool extends AbstractCachePool implements HierarchicalPoolInterface
{
    /**
     * {@inheritdoc}
     */
    protected function fetchObjectFromCache(string $key): array
    {
        return [false, null, [], null];
    }

    /**
     * {@inheritdoc}
     */
    protected function clearAllObjectsFromCache(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function clearOneObjectFromCache(string $key): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function storeItemInCache(PhpCacheItem $item, ?int $ttl): bool
    {
        return true;
    }

    /**
     * @param string[] $tags
     */
    public function clearTags(array $tags): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getList(string $name): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function removeList(string $name): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function appendListItem(string $name, string $key)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function removeListItem(string $name, string $key)
    {
    }
}
