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

trait CreateRedisClusterPoolTrait
{
    private ?\RedisCluster $client = null;

    public function createCachePool(): RedisCachePool
    {
        return new RedisCachePool($this->getClient());
    }

    private function getClient(): \RedisCluster
    {
        if ($this->client === null) {
            $this->client = new \RedisCluster(
                null,
                $this->getClientSeeds(),
            );
        }

        return $this->client;
    }

    /**
     * @return string[]
     */
    private function getClientSeeds(): array
    {
        $seeds = [];
        $initialPort = 7000;
        $index = 1;
        while ($host = getenv("CACHE_REDIS_CLUSTER_SEED{$index}_HOST")) {
            $defaultPort = $initialPort + ($index - 1);
            $port = ((int) getenv("CACHE_REDIS_CLUSTER_SEED{$index}_PORT")) ?: $defaultPort;
            $seeds[] = "$host:$port";

            $index++;
        }

        return $seeds;
    }

    public function createSimpleCache(): CacheInterface
    {
        return $this->createCachePool();
    }
}
