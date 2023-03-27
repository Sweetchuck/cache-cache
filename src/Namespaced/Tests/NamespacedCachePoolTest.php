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

use Cache\Namespaced\NamespacedCachePool;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;

/**
 * We should not use constants on interfaces in the tests. Tests should break if the constant is changed.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class NamespacedCachePoolTest extends TestCase
{
    /**
     * @return \Cache\Namespaced\Tests\HelperInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getHierarchyCacheStub()
    {
        return $this
            ->getMockBuilder(HelperInterface::class)
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
        $namespace = 'ns';
        $key = 'key';
        $item = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();

        $pool1 = $this->getHierarchyCacheStub();
        $pool1
            ->expects($this->once())
            ->method('getItem')
            ->with('|'.$namespace.'|'.$key)
            ->willReturn($item);

        $pool2 = new NamespacedCachePool($pool1, $namespace);
        static::assertSame($item, $pool2->getItem($key));
    }

    public function testGetItems(): void
    {
        $namespace = 'ns';
        $key0 = 'key0';
        $key1 = 'key1';
        $item0 = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();
        $item1 = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();

        $pool1 = $this->getHierarchyCacheStub();
        $pool1
            ->expects($this->once())
            ->method('getItems')
            ->with(["|$namespace|$key0", "|$namespace|$key1"])
            ->willReturn([
                "|$namespace|$key0" => $item0,
                "|$namespace|$key1" => $item1,
            ]);

        $pool2 = new NamespacedCachePool($pool1, $namespace);
        $actual = $pool2->getItems([$key0, $key1]);
        static::assertSame(
            [
                "|$namespace|$key0",
                "|$namespace|$key1",
            ],
            array_keys($actual),
        );
    }

    public function testHasItem(): void
    {
        $namespace = 'ns';
        $key = 'key';
        $returnValue = true;

        $stub = $this->getHierarchyCacheStub();
        $stub
            ->expects($this->once())
            ->method('hasItem')
            ->with("|$namespace|$key")
            ->willReturn($returnValue);

        $pool = new NamespacedCachePool($stub, $namespace);
        static::assertEquals($returnValue, $pool->hasItem($key));
    }

    public function testClear(): void
    {
        $namespace = 'ns';
        $returnValue = true;

        $stub = $this->getHierarchyCacheStub();
        $stub
            ->expects($this->once())
            ->method('deleteItem')
            ->with("|$namespace")
            ->willReturn($returnValue);

        $pool = new NamespacedCachePool($stub, $namespace);
        static::assertEquals($returnValue, $pool->clear());
    }

    public function testDeleteItem(): void
    {
        $namespace = 'ns';
        $key = 'key';
        $returnValue = true;

        $stub = $this->getHierarchyCacheStub();
        $stub
            ->expects($this->once())
            ->method('deleteItem')
            ->with("|$namespace|$key")
            ->willReturn($returnValue);

        $pool = new NamespacedCachePool($stub, $namespace);
        static::assertEquals($returnValue, $pool->deleteItem($key));
    }

    public function testDeleteItems(): void
    {
        $namespace = 'ns';
        $key0 = 'key0';
        $key1 = 'key1';
        $returnValue = true;

        $stub = $this->getHierarchyCacheStub();
        $stub
            ->expects($this->once())
            ->method('deleteItems')
            ->with(["|$namespace|$key0", "|$namespace|$key1"])
            ->willReturn($returnValue);

        $pool = new NamespacedCachePool($stub, $namespace);
        static::assertEquals($returnValue, $pool->deleteItems([$key0, $key1]));
    }

    public function testSave(): void
    {
        $item = $this->getMockBuilder(CacheItemInterface::class)->getMock();
        $namespace = 'ns';
        $returnValue = true;

        $stub = $this->getHierarchyCacheStub();
        $stub
            ->expects($this->once())
            ->method('save')
            ->with($item)
            ->willReturn($returnValue);

        $pool = new NamespacedCachePool($stub, $namespace);
        static::assertEquals($returnValue, $pool->save($item));
    }

    public function testSaveDeferred(): void
    {
        $item = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();
        $namespace = 'ns';
        $returnValue = true;

        $stub = $this->getHierarchyCacheStub();
        $stub
            ->expects($this->once())
            ->method('saveDeferred')
            ->with($item)
            ->willReturn($returnValue);

        $pool = new NamespacedCachePool($stub, $namespace);
        static::assertEquals($returnValue, $pool->saveDeferred($item));
    }

    public function testCommit(): void
    {
        $namespace = 'ns';
        $returnValue = true;

        $stub = $this->getHierarchyCacheStub();
        $stub
            ->expects($this->once())
            ->method('commit')
            ->willReturn($returnValue);

        $pool = new NamespacedCachePool($stub, $namespace);
        static::assertEquals($returnValue, $pool->commit());
    }
}
