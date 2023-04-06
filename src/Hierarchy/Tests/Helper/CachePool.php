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

namespace Cache\Hierarchy\Tests\Helper;

use Cache\Hierarchy\HierarchicalCachePoolTrait;

/**
 * A cache pool used in tests.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CachePool
{
    use HierarchicalCachePoolTrait;

    /**
     * @var string[]
     */
    private array $storeValues;

    /**
     * @param string[] $storeValues
     */
    public function __construct(array $storeValues = [])
    {
        $this->storeValues = $storeValues;
    }

    public function exposeClearHierarchyKeyCache(): void
    {
        $this->clearHierarchyKeyCache();
    }

    public function exposeGetHierarchyKey(string $key, ?string &$pathKey = null): string
    {
        return $this->getHierarchyKey($key, $pathKey);
    }

    protected function getDirectValue(string $name): mixed
    {
        return array_shift($this->storeValues);
    }
}
