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

use Cache\IntegrationTests\SimpleCacheTest;

class IntegrationSimpleCacheTest extends SimpleCacheTest
{
    use CreatePoolTrait;

    /**
     * {@inheritdoc}
     *
     * @phpstan-var array<string, string>
     */
    protected array $skippedTests = [
        'testDelete' => 'Version 5.8 does not return true when deleting non-existent item.',
        'testDeleteMultiple' => 'Version 5.8 does not return true when deleting non-existent item.',
        'testDeleteMultipleGenerator' => 'Version 5.8 does not return true when deleting non-existent item.',
    ];
}
