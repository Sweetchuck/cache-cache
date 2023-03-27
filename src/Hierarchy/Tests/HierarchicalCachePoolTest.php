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

namespace Cache\Hierarchy\Tests;

use Cache\Hierarchy\Tests\Helper\CachePool;
use PHPUnit\Framework\TestCase;

/**
 * We should not use constants on interfaces in the tests. Tests should break if the constant is changed.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class HierarchicalCachePoolTest extends TestCase
{
    public static function assertSameSha1(string $expected, ?string $result, string $message = ''): void
    {
        static::assertSame(sha1($expected), $result, $message);
    }

    public function testGetHierarchyKey(): void
    {
        $path = null;

        $pool   = new CachePool();
        $result = $pool->exposeGetHierarchyKey('key', $path);
        static::assertEquals('key', $result);
        static::assertNull($path);

        $pool   = new CachePool(['idx_1', 'idx_2', 'idx_3']);
        $result = $pool->exposeGetHierarchyKey('|foo|bar', $path);
        static::assertSameSha1('root!!idx_1!foo!!idx_2!bar!!idx_3!', $result);
        static::assertSameSha1('path!root!!idx_1!foo!!idx_2!bar!', $path);

        $pool   = new CachePool(['idx_1', 'idx_2', 'idx_3']);
        $result = $pool->exposeGetHierarchyKey('|', $path);
        static::assertSameSha1('path!root!', $path);
        static::assertSameSha1('root!!idx_1!', $result);
    }

    public function testGetHierarchyKeyWithTags(): void
    {
        $path = null;

        $pool   = new CachePool();
        $result = $pool->exposeGetHierarchyKey('key!tagHash', $path);
        static::assertEquals('key!tagHash', $result);
        static::assertNull($path);

        $pool   = new CachePool(['idx_1', 'idx_2', 'idx_3']);
        $result = $pool->exposeGetHierarchyKey('|foo|bar!tagHash', $path);
        static::assertSameSha1('root!tagHash!idx_1!foo!tagHash!idx_2!bar!tagHash!idx_3!', $result);
        static::assertSameSha1('path!root!tagHash!idx_1!foo!tagHash!idx_2!bar!tagHash', $path);

        $pool   = new CachePool(['idx_1', 'idx_2', 'idx_3']);
        $result = $pool->exposeGetHierarchyKey('|!tagHash', $path);
        static::assertSameSha1('path!root!tagHash', $path);
        static::assertSameSha1('root!tagHash!idx_1!', $result);
    }

    public function testGetHierarchyKeyEmptyCache(): void
    {
        $pool = new CachePool();
        $path = null;

        $result = $pool->exposeGetHierarchyKey('key', $path);
        static::assertEquals('key', $result);
        static::assertNull($path);

        $result = $pool->exposeGetHierarchyKey('|foo|bar', $path);
        static::assertSameSha1('root!!!foo!!!bar!!!', $result);
        static::assertSameSha1('path!root!!!foo!!!bar!', $path);

        $result = $pool->exposeGetHierarchyKey('|', $path);
        static::assertSameSha1('path!root!', $path);
        static::assertSameSha1('root!!!', $result);
    }

    public function testKeyCache(): void
    {
        $path = null;

        $pool   = new CachePool(['idx_1', 'idx_2', 'idx_3']);
        $result = $pool->exposeGetHierarchyKey('|foo', $path);
        static::assertSameSha1('root!!idx_1!foo!!idx_2!', $result);
        static::assertSameSha1('path!root!!idx_1!foo!', $path);

        // Make sure re reuse the old index value we already looked up for 'root'.
        $result = $pool->exposeGetHierarchyKey('|bar', $path);
        static::assertSameSha1('root!!idx_1!bar!!idx_3!', $result);
        static::assertSameSha1('path!root!!idx_1!bar!', $path);
    }

    public function testClearHierarchyKeyCache(): void
    {
        $pool = new CachePool();
        $prop = new \ReflectionProperty('Cache\Hierarchy\Tests\Helper\CachePool', 'keyCache');
        $prop->setAccessible(true);

        // add some values to the prop and make sure they are beeing cleared
        $prop->setValue($pool, ['foo' => 'bar', 'baz' => 'biz']);
        $pool->exposeClearHierarchyKeyCache();
        static::assertEmpty($prop->getValue($pool), 'The key cache must be cleared after ::ClearHierarchyKeyCache');
    }

    public function testIsHierarchyKey(): void
    {
        $pool   = new CachePool();
        $method = new \ReflectionMethod('Cache\Hierarchy\Tests\Helper\CachePool', 'isHierarchyKey');
        $method->setAccessible(true);

        static::assertFalse($method->invoke($pool, 'key'));
        static::assertFalse($method->invoke($pool, 'key|bar'));
        static::assertFalse($method->invoke($pool, 'key|'));
        static::assertTrue($method->invoke($pool, '|key'));
        static::assertTrue($method->invoke($pool, '|key|bar'));
    }

    public function testExplodeKey(): void
    {
        $pool   = new CachePool();
        $method = new \ReflectionMethod('Cache\Hierarchy\Tests\Helper\CachePool', 'explodeKey');
        $method->setAccessible(true);

        $result = $method->invoke($pool, '|key');
        static::assertCount(2, $result);
        static::assertEquals('key!', $result[1]);
        static::assertTrue(in_array('key!', $result));

        $result = $method->invoke($pool, '|key|bar');
        static::assertCount(3, $result);
        static::assertTrue(in_array('key!', $result));
        static::assertTrue(in_array('bar!', $result));

        $result = $method->invoke($pool, '|');
        static::assertCount(1, $result);
    }

    public function testExplodeKeyWithTags(): void
    {
        $pool   = new CachePool();
        $method = new \ReflectionMethod(CachePool::class, 'explodeKey');
        $method->setAccessible(true);

        $result = $method->invoke($pool, '|key|bar!hash');
        static::assertCount(3, $result);
        foreach ($result as $r) {
            static::assertMatchesRegularExpression(
                '|.*!hash|s',
                $r,
                'Tag hash must be on every level in hierarchy key',
            );
        }

        $result = $method->invoke($pool, '|!hash');
        static::assertCount(1, $result);
        static::assertMatchesRegularExpression(
            '|.*!hash|s',
            $result[0],
            'Tag hash must on root level in hierarchy key',
        );
    }
}
