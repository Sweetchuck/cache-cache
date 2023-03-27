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

namespace Cache\Prefixed\Tests;

use Cache\Prefixed\PrefixedCachePool;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class PrefixedCachePoolTest extends TestCase
{
    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&\Psr\Cache\CacheItemPoolInterface
     */
    private function getPoolMock()
    {
        return $this
            ->getMockBuilder(CacheItemPoolInterface::class)
            ->onlyMethods([
                'getItem',
                'getItems',
                'hasItem',
                'clear',
                'deleteItem',
                'deleteItems',
                'save',
                'saveDeferred',
                'commit',
            ])
            ->getMock();
    }

    public function testGetItem(): void
    {
        $prefix = 'ns';
        $key = 'key';
        $item = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();

        $pool1 = $this->getPoolMock();
        $pool1
            ->expects($this->once())
            ->method('getItem')
            ->with($prefix.$key)
            ->willReturn($item);

        $pool2 = new PrefixedCachePool($pool1, $prefix);
        static::assertEquals($item, $pool2->getItem($key));
    }

    public function testGetItems():void
    {
        $prefix = 'ns';
        $key0 = 'key0';
        $key1 = 'key1';
        $item0 = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();
        $item1 = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();

        $pool1 = $this->getPoolMock();
        $pool1
            ->expects($this->once())
            ->method('getItems')
            ->with([$prefix.$key0, $prefix.$key1])
            ->willReturn([
                $key0 => $item0,
                $key1 => $item1,
            ]);

        $pool2 = new PrefixedCachePool($pool1, $prefix);
        $actual = $pool2->getItems([$key0, $key1]);
        static::assertEquals(
            [
                $key0,
                $key1,
            ],
            array_keys($actual),
        );
    }

    public function testHasItem(): void
    {
        $prefix = 'ns';
        $key = 'key';
        $returnValue = true;

        $stub = $this->getPoolMock();
        $stub
            ->expects($this->once())
            ->method('hasItem')
            ->with($prefix.$key)
            ->willReturn($returnValue);

        $pool = new PrefixedCachePool($stub, $prefix);
        static::assertEquals($returnValue, $pool->hasItem($key));
    }

    public function testClear(): void
    {
        $prefix = 'ns';
        $returnValue = true;

        $stub = $this->getPoolMock();
        $stub
            ->expects($this->once())
            ->method('clear')
            ->willReturn($returnValue);

        $pool = new PrefixedCachePool($stub, $prefix);
        static::assertEquals($returnValue, $pool->clear());
    }

    public function testDeleteItem(): void
    {
        $prefix = 'ns';
        $key = 'key';
        $returnValue = true;

        $stub = $this->getPoolMock();
        $stub
            ->expects($this->once())
            ->method('deleteItem')
            ->with($prefix.$key)
            ->willReturn($returnValue);

        $pool = new PrefixedCachePool($stub, $prefix);
        static::assertEquals($returnValue, $pool->deleteItem($key));
    }

    public function testDeleteItems(): void
    {
        $prefix = 'ns';
        $key0 = 'key0';
        $key1 = 'key1';
        $returnValue = true;

        $stub = $this->getPoolMock();
        $stub
            ->expects($this->once())
            ->method('deleteItems')
            ->with([$prefix.$key0, $prefix.$key1])
            ->willReturn($returnValue);

        $pool = new PrefixedCachePool($stub, $prefix);
        static::assertEquals($returnValue, $pool->deleteItems([$key0, $key1]));
    }

    public function testSave(): void
    {
        $item = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();
        $prefix = 'ns';
        $returnValue = true;

        $stub = $this->getPoolMock();
        $stub
            ->expects($this->once())
            ->method('save')
            ->with($item)
            ->willReturn($returnValue);

        $pool = new PrefixedCachePool($stub, $prefix);
        static::assertEquals($returnValue, $pool->save($item));
    }

    public function testSaveDeferred(): void
    {
        $item = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();
        $prefix = 'ns';
        $returnValue = true;

        $stub = $this->getPoolMock();
        $stub
            ->expects($this->once())
            ->method('saveDeferred')
            ->with($item)
            ->willReturn($returnValue);

        $pool = new PrefixedCachePool($stub, $prefix);
        static::assertEquals($returnValue, $pool->saveDeferred($item));
    }

    public function testCommit(): void
    {
        $prefix = 'ns';
        $returnValue = true;

        $stub = $this->getPoolMock();
        $stub
            ->expects($this->once())
            ->method('commit')
            ->willReturn($returnValue);

        $pool = new PrefixedCachePool($stub, $prefix);
        static::assertEquals($returnValue, $pool->commit());
    }
}
