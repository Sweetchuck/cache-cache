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

namespace Cache\Adapter\PHPArray\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;

trait CreatePoolTrait
{

    /**
     * @var array<string, array<string>>
     */
    private array $cacheArray = [];

    public function createCachePool(): ArrayCachePool
    {
        return new ArrayCachePool(null, $this->cacheArray);
    }

    public function createSimpleCache(): ArrayCachePool
    {
        return $this->createCachePool();
    }
}
