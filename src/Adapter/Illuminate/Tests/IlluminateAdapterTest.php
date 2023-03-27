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

namespace Cache\Adapter\Illuminate\Tests;

use Cache\Adapter\Common\CacheItem;
use Cache\Adapter\Illuminate\IlluminateCachePool;
use Illuminate\Contracts\Cache\Store;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class IlluminateAdapterTest extends TestCase
{
    private IlluminateCachePool $pool;

    /**
     * @var \Cache\Adapter\Common\CacheItem&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockItem;

    /**
     * @var \Illuminate\Contracts\Cache\Store&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockItem = $this
            ->getMockBuilder(CacheItem::class)
            ->setConstructorArgs(['my-key-01'])
            ->getMock();

        $this->mockStore = $this
            ->getMockBuilder(Store::class)
            ->getMock();

        $this->pool = new IlluminateCachePool($this->mockStore);
    }

    public function testConstructor(): void
    {
        $this->pool = new IlluminateCachePool($this->mockStore);
        static::assertInstanceOf(IlluminateCachePool::class, $this->pool);
        static::assertInstanceOf(CacheItemPoolInterface::class, $this->pool);
    }
}
