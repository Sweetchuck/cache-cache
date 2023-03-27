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

use Psr\SimpleCache\CacheInterface;

/**
 * PrefixedSimpleCache.
 *
 * Prefixes all cache keys with a string.
 *
 * @author ndobromirov
 */
class PrefixedSimpleCache implements CacheInterface
{
    use PrefixedUtilityTrait;

    private CacheInterface $cache;

    public function __construct(CacheInterface $simpleCache, string $prefix)
    {
        $this->cache  = $simpleCache;
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->prefixValue($key);

        return $this->cache->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $this->prefixValues($keys);

        return $this->cache->deleteMultiple($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->prefixValue($key);

        return $this->cache->get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $oldKeys = (array) $keys;
        $this->prefixValues($keys);
        $keysMap = array_combine($keys, $oldKeys);

        $data = $this->cache->getMultiple($keys, $default);

        // As ordering is configuration dependent, remap the results.
        $result = [];
        foreach ($data as $key => $value) {
            $result[$keysMap[$key]] = $value;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $this->prefixValue($key);

        return $this->cache->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->prefixValue($key);

        return $this->cache->set($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $prefixed = [];
        foreach ($values as $key => $value) {
            $this->prefixValue($key);
            $prefixed[$key] = $value;
        }

        return $this->cache->setMultiple($prefixed, $ttl);
    }
}
