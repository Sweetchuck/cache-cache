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

namespace Cache\Adapter\Common;

/**
 * This trait could be used by adapters that do not have a native support for lists.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
trait TagSupportWithArray
{
    /**
     * Get a value from the storage.
     */
    abstract public function getDirectValue(string $name): mixed;

    /**
     * Set a value to the storage.
     *
     * @return void
     *
     * @todo Return type hint definition. Maybe static.
     */
    abstract public function setDirectValue(string $name, mixed $value);

    /**
     * @see \Cache\Adapter\Common\AbstractCachePool::appendListItem
     *
     * @return void
     *
     * @todo Return type hint definition. Maybe static.
     */
    protected function appendListItem(string $name, string $key)
    {
        $data = $this->getDirectValue($name);
        if (!is_array($data)) {
            $data = [];
        }
        $data[] = $key;
        $this->setDirectValue($name, $data);
    }

    /**
     * @see \Cache\Adapter\Common\AbstractCachePool::getList
     */
    protected function getList(string $name): array
    {
        $data = $this->getDirectValue($name);
        if (!is_array($data)) {
            $data = [];
        }

        return $data;
    }

    /**
     * @see \Cache\Adapter\Common\AbstractCachePool::removeList
     */
    protected function removeList(string $name): bool
    {
        $this->setDirectValue($name, []);

        return true;
    }

    /**
     * @see \Cache\Adapter\Common\AbstractCachePool::removeListItem
     *
     * @return void
     *
     * @todo Return type hint definition. Maybe static.
     */
    protected function removeListItem(string $name, string $key)
    {
        $data = $this->getList($name);
        foreach ($data as $i => $value) {
            if ($key === $value) {
                unset($data[$i]);
            }
        }

        $this->setDirectValue($name, $data);
    }
}
