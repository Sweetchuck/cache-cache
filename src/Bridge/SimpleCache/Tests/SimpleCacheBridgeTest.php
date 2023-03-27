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

namespace Cache\Bridge\SimpleCache\Tests;

use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class SimpleCacheBridgeTest extends TestCase
{
    private SimpleCacheBridge $bridge;

    /**
     * @phpstan-var \PHPUnit\Framework\MockObject\MockObject&\Psr\Cache\CacheItemPoolInterface
     */
    private $poolMock;

    /**
     * @phpstan-var \PHPUnit\Framework\MockObject\MockObject&\Psr\Cache\CacheItemInterface
     */
    private $itemMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poolMock = $this
            ->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();

        $this->bridge = new SimpleCacheBridge($this->poolMock);

        $this->itemMock = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();
    }

    public function testConstructor(): void
    {
        static::assertInstanceOf(SimpleCacheBridge::class, $this->bridge);
    }

    public function testFetch(): void
    {
        $this
            ->itemMock
            ->expects(static::once())
            ->method('isHit')
            ->willReturn(true);
        $this
            ->itemMock
            ->expects(static::once())
            ->method('get')
            ->willReturn('some_value');

        $this
            ->poolMock
            ->expects(static::once())
            ->method('getItem')
            ->willReturn($this->itemMock);

        static::assertSame('some_value', $this->bridge->get('some_item'));
    }

    public function testFetchMiss(): void
    {
        $this
            ->itemMock
            ->expects(static::once())
            ->method('isHit')
            ->willReturn(false);

        $this
            ->poolMock
            ->expects(static::once())
            ->method('getItem')
            ->with('no_item')
            ->willReturn($this->itemMock);

        static::assertFalse($this->bridge->get('no_item', false));
    }

    public function testContains(): void
    {
        $this
            ->poolMock
            ->method('hasItem')
            ->willReturnMap([
                ['no_item', false],
                ['some_item', true],
            ]);

        static::assertFalse($this->bridge->has('no_item'));
        static::assertTrue($this->bridge->has('some_item'));
    }

    public function testSave(): void
    {
        $this
            ->itemMock
            ->method('set')
            ->with('dummy_data')
            ->willReturnSelf();
        $this
            ->itemMock
            ->method('expiresAfter')
            ->willReturnMap([
                [null, $this->itemMock],
                [2, $this->itemMock],
            ]);

        $this
            ->poolMock
            ->method('getItem')
            ->with('some_item')
            ->willReturn($this->itemMock);
        $this
            ->poolMock
            ->method('save')
            ->with($this->itemMock)
            ->willReturn(true);

        static::assertTrue($this->bridge->set('some_item', 'dummy_data'));
        static::assertTrue($this->bridge->set('some_item', 'dummy_data', 2));
    }

    public function testDelete(): void
    {
        $this
            ->poolMock
            ->expects(static::once())
            ->method('deleteItem')
            ->with('some_item')
            ->willReturn(true);

        static::assertTrue($this->bridge->delete('some_item'));
    }
}
