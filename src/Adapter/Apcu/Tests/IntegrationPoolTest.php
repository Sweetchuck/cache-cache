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

namespace Cache\Adapter\Apcu\Tests;

use Cache\Adapter\Apcu\ApcuCachePool;
use Cache\IntegrationTests\CachePoolTest as BaseTest;
use Psr\Cache\CacheItemPoolInterface;

class IntegrationPoolTest extends BaseTest
{

    /**
     * {@inheritdoc}
     *
     * @phpstan-var array<string, string>
     */
    protected array $skippedTests = [
        'testExpiration' => 'The cache expire at the next request.',
        'testSaveExpired' => 'The cache expire at the next request.',
        'testDeferredExpired' => 'The cache expire at the next request.',
    ];

    public function createCachePool(): CacheItemPoolInterface
    {
        if (defined('HHVM_VERSION')
            || !function_exists('apcu_store')
            || (function_exists('apcu_enabled') && !apcu_enabled())
        ) {
            $this->markTestSkipped();
        }

        return new ApcuCachePool();
    }
}
