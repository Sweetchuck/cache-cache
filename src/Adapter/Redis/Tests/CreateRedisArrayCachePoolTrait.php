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

namespace Cache\Adapter\Redis\Tests;

use Cache\Adapter\Redis\RedisCachePool;
use Psr\SimpleCache\CacheInterface;

trait CreateRedisArrayCachePoolTrait
{
    private ?\RedisArray $client = null;

    public function createCachePool(): RedisCachePool
    {
        return new RedisCachePool($this->getClient());
    }

    private function getClient(): \RedisArray
    {
        if ($this->client === null) {
            $host = getenv('CACHE_REDIS_SERVER1_HOST') ?: '127.0.0.1';
            $port = ((int) getenv('CACHE_REDIS_SERVER1_PORT')) ?: 6379;
            $this->client = new \RedisArray(["$host:$port"]);
        }

        return $this->client;
    }

    public function createSimpleCache(): CacheInterface
    {
        return $this->createCachePool();
    }

    /**
     * @after
     */
    public function tearDownService()
    {
        parent::tearDownService();
        $this->client?->close();
    }
}
