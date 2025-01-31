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

namespace Cache\Bridge\SimpleCache\Tests;

use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use Cache\IntegrationTests\SimpleCacheTest as BaseTest;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class IntegrationTest extends BaseTest
{
    public function createSimpleCache(): CacheInterface
    {
        return new SimpleCacheBridge(new ArrayAdapter());
    }
}
