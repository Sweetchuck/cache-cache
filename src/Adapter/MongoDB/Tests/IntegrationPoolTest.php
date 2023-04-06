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
use Cache\IntegrationTests\CachePoolTest;
use Psr\Cache\CacheItemPoolInterface;

class IntegrationPoolTest extends CachePoolTest
{
    use CreateServerTrait;

    public function createCachePool(): CacheItemPoolInterface
    {
        return new MongoDBCachePool($this->getCollection());
    }
}
