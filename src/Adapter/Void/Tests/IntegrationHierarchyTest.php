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

namespace Cache\Adapter\Void\Tests;

use Cache\Adapter\Void\VoidCachePool;
use Cache\IntegrationTests\HierarchicalCachePoolTest;
use Psr\Cache\CacheItemPoolInterface;

class IntegrationHierarchyTest extends HierarchicalCachePoolTest
{

    /**
     * {@inheritdoc}
     */
    protected array $skippedTests = [
        'testBasicUsage'          => 'Void adapter does not save,',
        'testChain'               => 'Void adapter does not save,',
        'testRemoval'             => 'Void adapter does not save,',
        'testRemovalWhenDeferred' => 'Void adapter does not save,',
    ];

    public function createCachePool(): CacheItemPoolInterface
    {
        return new VoidCachePool();
    }
}
