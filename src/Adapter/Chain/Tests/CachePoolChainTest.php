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

namespace Cache\Adapter\Chain\Tests;

use Cache\Adapter\Chain\CachePoolChain;
use Cache\Adapter\Common\CacheItem;
use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;

/**
 * Class ChainPoolTest.
 */
class CachePoolChainTest extends TestCase
{
    public function testGetItemStoreToPrevious(): void
    {
        $firstPool  = new ArrayCachePool();
        $secondPool = new ArrayCachePool();
        $chainPool  = new CachePoolChain([$firstPool, $secondPool]);

        $key  = 'test_key';
        $item = new CacheItem($key, true, 'value');
        $item->expiresAfter(60);
        $secondPool->save($item);

        $loadedItem = $firstPool->getItem($key);
        static::assertFalse($loadedItem->isHit());

        $loadedItem = $secondPool->getItem($key);
        static::assertTrue($loadedItem->isHit());

        $loadedItem = $chainPool->getItem($key);
        static::assertTrue($loadedItem->isHit());

        $loadedItem = $firstPool->getItem($key);
        static::assertTrue($loadedItem->isHit());
    }

    public function testGetItemsStoreToPrevious(): void
    {
        $firstPool  = new ArrayCachePool();
        $secondPool = new ArrayCachePool();
        $chainPool  = new CachePoolChain([$firstPool, $secondPool]);

        $key  = 'test_key';
        $item = new CacheItem($key, true, 'value');
        $item->expiresAfter(60);
        $secondPool->save($item);
        $firstExpirationTime = $item->getExpirationTimestamp();

        $key2 = 'test_key2';
        $item = new CacheItem($key2, true, 'value2');
        $item->expiresAfter(60);
        $secondPool->save($item);
        $secondExpirationTime = $item->getExpirationTimestamp();

        $loadedItem = $firstPool->getItem($key);
        static::assertFalse($loadedItem->isHit());

        $loadedItem = $firstPool->getItem($key2);
        static::assertFalse($loadedItem->isHit());

        $loadedItem = $secondPool->getItem($key);
        static::assertTrue($loadedItem->isHit());

        $loadedItem = $secondPool->getItem($key2);
        static::assertTrue($loadedItem->isHit());

        $items = $chainPool->getItems([$key, $key2]);

        static::assertArrayHasKey($key, $items);
        static::assertArrayHasKey($key2, $items);

        static::assertTrue($items[$key]->isHit());
        static::assertTrue($items[$key2]->isHit());

        $loadedItem = $firstPool->getItem($key);
        static::assertTrue($loadedItem->isHit());
        static::assertEquals($firstExpirationTime, $loadedItem->getExpirationTimestamp());

        $loadedItem = $firstPool->getItem($key2);
        static::assertTrue($loadedItem->isHit());
        static::assertEquals($secondExpirationTime, $loadedItem->getExpirationTimestamp());
    }

    public function testGetItemsWithEmptyCache(): void
    {
        $firstPool  = new ArrayCachePool();
        $secondPool = new ArrayCachePool();
        $chainPool  = new CachePoolChain([$firstPool, $secondPool]);

        $key  = 'test_key';
        $key2 = 'test_key2';

        $items = $chainPool->getItems([$key, $key2]);

        static::assertArrayHasKey($key, $items);
        static::assertArrayHasKey($key2, $items);

        static::assertFalse($items[$key]->isHit());
        static::assertFalse($items[$key2]->isHit());
    }
}
