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

namespace Cache\Adapter\PHPArray\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;

class ArrayCachePoolTest extends TestCase
{
    public function testLimit(): void
    {
        $pool = new ArrayCachePool(2);
        $item = $pool->getItem('key1')->set('value1');
        $pool->save($item);

        $item = $pool->getItem('key2')->set('value2');
        $pool->save($item);

        // Both items should be in the pool, nothing strange yet
        static::assertTrue($pool->hasItem('key1'));
        static::assertTrue($pool->hasItem('key2'));

        $item = $pool->getItem('key3')->set('value3');
        $pool->save($item);

        // First item should be dropped
        static::assertFalse($pool->hasItem('key1'));
        static::assertTrue($pool->hasItem('key2'));
        static::assertTrue($pool->hasItem('key3'));

        static::assertFalse($pool->getItem('key1')->isHit());
        static::assertTrue($pool->getItem('key2')->isHit());
        static::assertTrue($pool->getItem('key3')->isHit());

        $item = $pool->getItem('key4')->set('value4');
        $pool->save($item);

        // Only the last two items should be in place
        static::assertFalse($pool->hasItem('key1'));
        static::assertFalse($pool->hasItem('key2'));
        static::assertTrue($pool->hasItem('key3'));
        static::assertTrue($pool->hasItem('key4'));
    }

    public function testRemoveListItem(): void
    {
        $pool       = new ArrayCachePool();
        $reflection = new \ReflectionClass(get_class($pool));
        $method     = $reflection->getMethod('removeListItem');
        $method->setAccessible(true);

        // Add a tagged item to test list removal
        $item = $pool->getItem('key1')->set('value1')->setTags(['tag1']);
        $pool->save($item);

        static::assertTrue($pool->hasItem('key1'));
        static::assertTrue($pool->deleteItem('key1'));
        static::assertFalse($pool->hasItem('key1'));

        // Trying to remove an item in an un-existing tag list should not throw
        // Notice error / Exception in strict mode
        static::assertNull($method->invokeArgs($pool, ['tag1', 'key1']));
    }
}
