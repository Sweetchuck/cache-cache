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

namespace Cache\Bridge\Doctrine\Tests;

use Cache\Bridge\Doctrine\DoctrineCacheBridge;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class DoctrineCacheBridgeTest extends TestCase
{
    private DoctrineCacheBridge $bridge;

    /**
     * @var \Psr\Cache\CacheItemPoolInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $poolMock;

    /**
     * @var \Psr\Cache\CacheItemInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemRoot;

    /**
     * @var \Psr\Cache\CacheItemInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poolMock = $this
            ->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();

        $this->itemMock = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();


        $this->itemRoot = $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMock();
        $this
            ->itemRoot
            ->method('isHit')
            ->willReturn(false);

        $this->bridge = new DoctrineCacheBridge($this->poolMock);
    }

    public function testConstructor(): void
    {
        static::assertInstanceOf(DoctrineCacheBridge::class, $this->bridge);
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
            ->method('getItem')
            ->willReturnMap([
                ['DoctrineNamespaceCacheKey[]', $this->itemRoot],
                ['[some_item][1]', $this->itemMock],
            ]);

        static::assertSame('some_value', $this->bridge->fetch('some_item'));
    }

    public function testFetchMiss(): void
    {
        $this
            ->itemMock
            ->method('isHit')
            ->willReturn(false);

        $this
            ->poolMock
            ->method('getItem')
            ->willReturnMap([
                ['DoctrineNamespaceCacheKey[]', $this->itemRoot],
                ['[no_item][1]', $this->itemMock],
            ]);

        static::assertFalse($this->bridge->fetch('no_item'));
    }

    public function testContains(): void
    {
        $this
            ->poolMock
            ->method('getItem')
            ->willReturnMap([
                ['DoctrineNamespaceCacheKey[]', $this->itemRoot],
            ]);
        $this
            ->poolMock
            ->method('hasItem')
            ->willReturnMap([
                ['[no_item][1]', false],
                ['[some_item][1]', true],
            ]);

        static::assertFalse($this->bridge->contains('no_item'));
        static::assertTrue($this->bridge->contains('some_item'));
    }

    public function testSave(): void
    {
        $this
            ->itemMock
            ->method('set')
            ->willReturnSelf();
        $this
            ->itemMock
            ->method('expiresAfter')
            ->with(2)
            ->willReturnSelf();

        $this
            ->poolMock
            ->method('getItem')
            ->willReturnMap([
                ['DoctrineNamespaceCacheKey[]', $this->itemRoot],
                ['[some_item][1]', $this->itemMock],
            ]);
        $this
            ->poolMock
            ->method('save')
            ->with($this->itemMock)
            ->willReturn(true);

        static::assertTrue($this->bridge->save('some_item', 'dummy_data'));
        static::assertTrue($this->bridge->save('some_item', 'dummy_data', 2));
    }

    public function testDelete(): void
    {
        $this
            ->poolMock
            ->expects(static::once())
            ->method('deleteItem')
            ->with('[some_item][1]')
            ->willReturn(true);

        static::assertTrue($this->bridge->delete('some_item'));
    }

    public function testGetCache(): void
    {
        static::assertInstanceOf(CacheItemPoolInterface::class, $this->bridge->getCachePool());
    }

    public function testGetStats(): void
    {
        static::assertEmpty($this->bridge->getStats());
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testInvalidKeys(string $key, string $normalizedKey): void
    {
        $normalizedKey = sprintf('[%s][1]', $normalizedKey);

        $this
            ->itemMock
            ->method('isHit')
            ->willReturn(false);
        $this
            ->itemMock
            ->method('set')
            ->willReturnSelf();

        $this
            ->poolMock
            ->method('getItem')
            ->willReturnMap([
                ['DoctrineNamespaceCacheKey[]', $this->itemRoot],
                [$normalizedKey, $this->itemMock],
            ]);
        $this
            ->poolMock
            ->method('hasItem')
            ->with($normalizedKey)
            ->willReturn(false);
        $this
            ->poolMock
            ->method('deleteItem')
            ->with($normalizedKey);
        $this
            ->poolMock
            ->method('save');

        $this->bridge->contains($key);
        $this->bridge->save($key, 'foo');
        $this->bridge->fetch($key);
        $this->bridge->delete($key);
        static::assertTrue(true, '@todo Figure it out why the normalized keys are clean');
    }

    /**
     * Data provider for invalid keys.
     *
     * @phpstan-return array<array<string>>
     */
    public static function invalidKeys(): array
    {
        return [
            ['{str', '_str'],
            ['rand{', 'rand_'],
            ['rand{str', 'rand_str'],
            ['rand}str', 'rand_str'],
            ['rand(str', 'rand_str'],
            ['rand)str', 'rand_str'],
            ['rand/str', 'rand_str'],
            ['rand\\str', 'rand_str'],
            ['rand@str', 'rand_str'],
            ['rand:str', 'rand_str'],
        ];
    }
}
