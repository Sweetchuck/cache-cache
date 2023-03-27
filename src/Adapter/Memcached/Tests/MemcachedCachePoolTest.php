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

namespace Cache\Adapter\Memcached\Tests;

use PHPUnit\Framework\TestCase;

class MemcachedCachePoolTest extends TestCase
{
    use CreatePoolTrait;

    /**
     * @after
     */
    public function tearDownService(): void
    {
        $this->client?->quit();
    }

    /**
     * Ensures that items with a TTL larger than 30 days can be stored in memcached
     * https://github.com/memcached/memcached/wiki/Programming#expiration.
     */
    public function testTimeToLiveMoreThan30days(): void
    {
        $pool = $this->createCachePool();

        $item = $pool->getItem('365days');
        $item->set('4711');
        $item->expiresAfter(86400 * 365);
        $pool->save($item);

        static::assertTrue(
            $pool->getItem('365days')->isHit(),
            'Item is not stored correctly',
        );
    }
}
