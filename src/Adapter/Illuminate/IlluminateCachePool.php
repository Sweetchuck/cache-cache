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

namespace Cache\Adapter\Illuminate;

use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;
use Cache\Hierarchy\HierarchicalCachePoolTrait;
use Cache\Hierarchy\HierarchicalPoolInterface;
use Illuminate\Contracts\Cache\Store;

/**
 * This is a bridge between PSR-6 and an Illuminate cache store.
 *
 * @author Florian Voutzinos <florian@voutzinos.com>
 */
class IlluminateCachePool extends AbstractCachePool implements HierarchicalPoolInterface
{
    use HierarchicalCachePoolTrait;

    /**
     * @var Store
     */
    protected $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * {@inheritdoc}
     */
    protected function storeItemInCache(PhpCacheItem $item, ?int $ttl): bool
    {
        $ttl = null === $ttl ? 0 : $ttl / 60;

        $data = serialize([true, $item->get(), $item->getTags(), $item->getExpirationTimestamp()]);

        $this->store->put($this->getHierarchyKey($item->getKey()), $data, $ttl);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchObjectFromCache(string $key): array
    {
        if (null === $data = $this->store->get($this->getHierarchyKey($key))) {
            return [false, null, [], null];
        }

        return unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    protected function clearAllObjectsFromCache(): bool
    {
        return $this->store->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function clearOneObjectFromCache(string $key): bool
    {
        $path      = null;
        $keyString = $this->getHierarchyKey($key, $path);
        if ($path) {
            if ($this->store->get($path) === null) {
                $this->store->put($path, 0, 0);
            }
            $this->store->increment($path);
        }
        $this->clearHierarchyKeyCache();

        return $this->store->forget($keyString);
    }

    /**
     * {@inheritdoc}
     */
    protected function getList(string $name): array
    {
        $list = $this->store->get($name);

        if (!is_array($list)) {
            return [];
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    protected function removeList(string $name): bool
    {
        return $this->store->forget($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function appendListItem(string $name, string $key)
    {
        $list   = $this->getList($name);
        $list[] = $key;

        $this->store->forever($name, $list);
    }

    /**
     * {@inheritdoc}
     */
    protected function removeListItem(string $name, string $key)
    {
        $list = $this->getList($name);

        foreach ($list as $i => $item) {
            if ($item === $key) {
                unset($list[$i]);
            }
        }

        $this->store->forever($name, $list);
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectValue(string $name): mixed
    {
        return $this->store->get($name);
    }
}
