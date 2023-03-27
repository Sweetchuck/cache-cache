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

namespace Cache\Prefixed;

/**
 * Utility to reduce code supplication between the Prefixed* decorators.
 *
 * @author ndobromirov
 */
trait PrefixedUtilityTrait
{
    /**
     * Prefix to use for key namespaceing.
     */
    private string $prefix = '';

    /**
     * Add namespace prefix on the key.
     */
    private function prefixValue(string &$key): static
    {
        $key = $this->prefix.$key;

        return $this;
    }

    /**
     * Adds a namespace prefix on a list of keys.
     *
     * @param string[] $keys
     *   Reference to the list of keys. The list is mutated.
     */
    private function prefixValues(array &$keys): static
    {
        foreach ($keys as &$key) {
            $this->prefixValue($key);
        }

        return $this;
    }
}
