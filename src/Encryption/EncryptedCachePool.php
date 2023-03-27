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

namespace Cache\Encryption;

use Cache\Adapter\Common\Exception\InvalidArgumentException;
use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Defuse\Crypto\Key;
use Psr\Cache\CacheItemInterface;

/**
 * Wraps a CacheItemInterface with EncryptedItemDecorator.
 *
 * @author Daniel Bannert <d.bannert@anolilab.de>
 */
class EncryptedCachePool implements TaggableCacheItemPoolInterface
{
    private TaggableCacheItemPoolInterface $cachePool;

    private Key $key;

    public function __construct(TaggableCacheItemPoolInterface $cachePool, Key $key)
    {
        $this->cachePool = $cachePool;
        $this->key       = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): TaggableCacheItemInterface
    {
        $items = $this->getItems([$key]);
        // Iterable $items always contains one item,
        // therefore \reset() never gonna give (bool) FALSE.
        // (Hopefully $items is seekable.)
        /** @var \Cache\TagInterop\TaggableCacheItemInterface $item */
        $item = reset($items);

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): iterable
    {
        return array_map(
            function (CacheItemInterface $inner) {
                return $inner instanceof EncryptedItemDecorator ?
                    $inner
                    : new EncryptedItemDecorator($inner, $this->key);
            },
            (array) $this->cachePool->getItems($keys),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
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
        return $this->cachePool->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        return $this->cachePool->deleteItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        static::assertCacheItem($item);

        /** @var \Cache\Encryption\EncryptedItemDecorator $item */
        return $this->cachePool->save($item->getCacheItem());
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        static::assertCacheItem($item);

        /** @var \Cache\Encryption\EncryptedItemDecorator $item */
        return $this->cachePool->saveDeferred($item->getCacheItem());
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        return $this->cachePool->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags): bool
    {
        return $this->cachePool->invalidateTags($tags);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTag(string $tag): bool
    {
        return $this->cachePool->invalidateTag($tag);
    }

    protected function assertCacheItem(CacheItemInterface $item): void
    {
        if (!($item instanceof EncryptedItemDecorator)) {
            throw new InvalidArgumentException(sprintf(
                'Cache items are not transferable between pools. Item MUST implement %s.',
                EncryptedItemDecorator::class,
            ));
        }
    }
}
