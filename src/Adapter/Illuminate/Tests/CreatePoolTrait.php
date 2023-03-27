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

use Cache\Adapter\Illuminate\IlluminateCachePool;
use Illuminate\Cache\ArrayStore;

trait CreatePoolTrait
{
    private ?ArrayStore $illuminateStore = null;

    public function createCachePool(): IlluminateCachePool
    {
        return new IlluminateCachePool($this->getIlluminateStore());
    }

    public function createSimpleCache(): IlluminateCachePool
    {
        return $this->createCachePool();
    }

    private function getIlluminateStore(): ArrayStore
    {
        if ($this->illuminateStore === null) {
            $this->illuminateStore = new ArrayStore();
        }

        return $this->illuminateStore;
    }
}
