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

namespace Cache\Adapter\Doctrine\Tests;

use Cache\Adapter\Doctrine\DoctrineCachePool;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Psr\SimpleCache\CacheInterface;

trait CreatePoolTrait
{
    private ?Cache $doctrineCache = null;

    /**
     * @return \Cache\Adapter\Doctrine\DoctrineCachePool
     */
    public function createCachePool(): TaggableCacheItemPoolInterface
    {
        return new DoctrineCachePool($this->getDoctrineCache());
    }

    public function createSimpleCache(): CacheInterface
    {
        return $this->createCachePool();
    }

    private function getDoctrineCache(): Cache
    {
        if ($this->doctrineCache === null) {
            $this->doctrineCache = new FilesystemCache(sys_get_temp_dir().'/doctirnecache');
        }

        return $this->doctrineCache;
    }
}
