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

namespace Cache\Adapter\Memcache\Tests;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Cache\IntegrationTests\SimpleCacheTest;
use Psr\SimpleCache\CacheInterface;

class IntegrationSimpleCacheTest extends SimpleCacheTest
{
    private ?\Memcache $client = null;

    /**
     * @after
     */
    public function tearDownService(): void
    {
        parent::tearDownService();
        $this->client?->close();
    }

    public function createSimpleCache(): CacheInterface
    {
        if (!class_exists(\Memcache::class)) {
            static::markTestSkipped();
        }

        return new MemcacheCachePool($this->getClient());
    }

    private function getClient(): \Memcache
    {
        if ($this->client === null) {
            $this->client = new \Memcache();
            $this->client->connect(
                getenv('CACHE_MEMCACHE_SERVER1_HOST') ?: '127.0.0.1',
                (int) (getenv('CACHE_MEMCACHE_SERVER1_PORT') ?: '11211'),
            );
        }

        return $this->client;
    }
}
