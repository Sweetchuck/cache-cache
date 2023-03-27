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

namespace Cache\Util\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Util;
use PHPUnit\Framework\TestCase;

class SimpleCacheUtilTest extends TestCase
{
    public function testRememberCacheHit(): void
    {
        $cache = new ArrayCachePool();
        $cache->set('foo', 'bar');
        $res = Util\SimpleCache\remember($cache, 'foo', null, function () {
            throw new \Exception('bad');
        });
        static::assertEquals('bar', $res);
    }

    public function testRememberCacheMiss(): void
    {
        $cache = new ArrayCachePool();
        $res   = Util\SimpleCache\remember($cache, 'foo', null, function () {
            return 'bar';
        });
        static::assertEquals('bar', $res);
        static::assertEquals('bar', $cache->get('foo'));
    }
}
