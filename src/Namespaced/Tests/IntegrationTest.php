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

namespace Cache\Namespaced\Tests;

use Cache\Adapter\Memcached\MemcachedCachePool;
use Cache\Namespaced\NamespacedCachePool;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class IntegrationTest extends TestCase
{
    private ?MemcachedCachePool $cache;

    protected function setUp(): void
    {
        $cache = new \Memcached();
        $cache->addServer(
            getenv('CACHE_MEMCACHE_SERVER1_HOST') ?: '127.0.0.1',
            (int) (getenv('CACHE_MEMCACHE_SERVER1_PORT')) ?: 11211,
        );

        $this->cache = new MemcachedCachePool($cache);
    }

    protected function tearDown(): void
    {
        $this->cache?->clear();
    }

    public function testGetItem(): void
    {
        $namespace = 'ns';
        $key = 'myKey01';
        $nsPool = new NamespacedCachePool($this->cache, $namespace);

        $item = $nsPool->getItem($key);
        static::assertSame("|$namespace|$key", $item->getKey());
    }

    public function testGetItems(): void
    {
        $namespace = 'ns';
        $nsPool = new NamespacedCachePool($this->cache, $namespace);

        $items = $nsPool->getItems(['key0', 'key1']);

        $str = "|$namespace|key0";
        static::assertTrue(isset($items[$str]));
        static::assertSame($str, $items[$str]->getKey());

        $str = "|$namespace|key1";
        static::assertTrue(isset($items[$str]));
        static::assertSame($str, $items[$str]->getKey());
    }

    public function testSave(): void
    {
        $namespace = 'ns';
        $nsPool = new NamespacedCachePool($this->cache, $namespace);

        $item = $nsPool->getItem('key');
        $item->set('foo');
        $nsPool->save($item);

        static::assertTrue($nsPool->hasItem('key'));
        static::assertFalse($this->cache->hasItem('key'));
    }

    public function testSaveDeferred(): void
    {
        $namespace = 'ns';
        $nsPool = new NamespacedCachePool($this->cache, $namespace);

        $item = $nsPool->getItem('key');
        $item->set('foo');
        $nsPool->saveDeferred($item);

        static::assertTrue($nsPool->hasItem('key'));
        static::assertFalse($this->cache->hasItem('key'));

        $nsPool->commit();
        static::assertTrue($nsPool->hasItem('key'));
        static::assertFalse($this->cache->hasItem('key'));
    }
}
