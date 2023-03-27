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

namespace Cache\Adapter\MongoDB\Tests;

use Cache\Adapter\MongoDB\MongoDBCachePool;
use MongoDB\Collection;
use MongoDB\Driver\Manager;

/**
 * @link https://github.com/mongodb/mongo-php-driver/issues/393
 */
trait CreateServerTrait
{
    abstract public static function markTestSkipped(string $message = ''): never;

    private ?Collection $collection = null;

    public function getCollection(): Collection
    {
        if (!$this->collection) {
            $host = getenv('CACHE_MONGODB_SERVER1_HOST') ?: '127.0.0.1';
            $port = getenv('CACHE_MONGODB_SERVER1_PORT') ?: '27017';
            // In your own code, only do this *once* to initialize your cache.
            $this->collection = MongoDBCachePool::createCollection(
                new Manager("mongodb://$host:$port"),
                getenv('CACHE_MONGODB_SERVER1_DATABASE') ?: 'test',
                getenv('CACHE_MONGODB_SERVER1_COLLECTION') ?: 'psr6test.cache',
            );
        }

        return $this->collection;
    }

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Collection::class)) {
            static::markTestSkipped('PHP extension "mongodb" is not installed.');
        }

        parent::setUpBeforeClass();
    }
}
