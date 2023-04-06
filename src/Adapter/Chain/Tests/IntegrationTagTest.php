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

namespace Cache\Adapter\Chain\Tests;

use Cache\Adapter\Chain\CachePoolChain;
use Cache\IntegrationTests\TaggableCachePoolTest;
use Cache\TagInterop\TaggableCacheItemPoolInterface;

class IntegrationTagTest extends TaggableCachePoolTest
{
    use CreatePoolTrait;

    public function createCachePool(): TaggableCacheItemPoolInterface
    {
        return new CachePoolChain($this->getAdapters());
    }
}
