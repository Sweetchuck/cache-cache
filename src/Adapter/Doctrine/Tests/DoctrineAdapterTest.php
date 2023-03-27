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

namespace Cache\Adapter\Doctrine\Tests;

use Cache\Adapter\Common\CacheItem;
use Cache\Adapter\Doctrine\DoctrineCachePool;
use Doctrine\Common\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class DoctrineAdapterTest extends TestCase
{
    private DoctrineCachePool $pool;

    /**
     * @var \Cache\Adapter\Common\CacheItem&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockItem;

    /**
     * @var \Doctrine\Common\Cache\Cache&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockDoctrine;

    protected function setUp(): void
    {
        $this->mockItem = $this->createMock(CacheItem::class);
        $this->mockDoctrine = $this->createMock(Cache::class);

        $this->pool = new DoctrineCachePool($this->mockDoctrine);
    }

    public function testConstructor(): void
    {
        static::assertInstanceOf(DoctrineCachePool::class, $this->pool);
        static::assertInstanceOf(CacheItemPoolInterface::class, $this->pool);
    }

    public function testGetCache(): void
    {
        static::assertInstanceOf(Cache::class, $this->pool->getCache());
        static::assertEquals($this->mockDoctrine, $this->pool->getCache());
    }
}
