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

namespace Cache\Adapter\Predis\Tests;

use Cache\Adapter\Predis\PredisCachePool;
use Predis\Client;

trait CreatePoolTrait
{
    private ?Client $client = null;

    public function createCachePool(): PredisCachePool
    {
        return new PredisCachePool($this->getClient());
    }

    public function createSimpleCache(): PredisCachePool
    {
        return $this->createCachePool();
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $host = getenv('CACHE_REDIS_SERVER1_HOST') ?: '127.0.0.1';
            $port = ((int) getenv('CACHE_REDIS_SERVER1_PORT')) ?: 6379;
            $db = ((int) getenv('CACHE_REDIS_SERVER1_DB')) ?: 1;
            $this->client = new Client("tcp://$host:$port/$db");
        }

        return $this->client;
    }
}
