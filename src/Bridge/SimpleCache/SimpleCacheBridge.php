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

namespace Cache\Bridge\SimpleCache;

use Cache\Bridge\SimpleCache\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * Adds a SimpleCache interface on a PSR-6 cache pool.
 *
 * @author Magnus Nordlander <magnus@fervo.se>
 */
class SimpleCacheBridge implements CacheInterface
{
    protected CacheItemPoolInterface $cacheItemPool;

    public function __construct(CacheItemPoolInterface $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $item = $this->cacheItemPool->getItem($key);
        } catch (CacheInvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$item->isHit()) {
            return $default;
        }

        return $item->get();
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        try {
            $item = $this->cacheItemPool->getItem($key);
            $item->expiresAfter($ttl);
        } catch (CacheInvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $item->set($value);

        return $this->cacheItemPool->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        try {
            return $this->cacheItemPool->deleteItem($key);
        } catch (CacheInvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->cacheItemPool->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        if (!is_array($keys)) {
            if (!$keys instanceof \Traversable) {
                throw new InvalidArgumentException('$keys is neither an array nor Traversable');
            }

            // Since we need to throw an exception if *any* key is invalid, it doesn't
            // make sense to wrap iterators or something like that.
            $keys = iterator_to_array($keys, false);
        }

        try {
            $items = $this->cacheItemPool->getItems($keys);
        } catch (CacheInvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->generateValues($default, $items);
    }

    /**
     * @param iterable<\Psr\Cache\CacheItemInterface> $items
     *
     * @return \Generator
     */
    private function generateValues(mixed $default, iterable $items)
    {
        foreach ($items as $key => $item) {
            yield $key => ($item->isHit() ? $item->get() : $default);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-param array<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $keys = [];
        $arrayValues = [];
        foreach ($values as $key => $value) {
            static::assertKey($key);
            settype($key, 'string');

            $keys[] = $key;
            $arrayValues[$key] = $value;
        }

        try {
            $items = $this->cacheItemPool->getItems($keys);
        } catch (CacheInvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $itemSuccess = true;

        foreach ($items as $key => $item) {
            $item->set($arrayValues[$key]);

            try {
                $item->expiresAfter($ttl);
            } catch (CacheInvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            $itemSuccess = $itemSuccess && $this->cacheItemPool->saveDeferred($item);
        }

        return $itemSuccess && $this->cacheItemPool->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        if (!is_array($keys)) {
            if (!$keys instanceof \Traversable) {
                throw new InvalidArgumentException('$keys is neither an array nor Traversable');
            }

            // Since we need to throw an exception if *any* key is invalid, it doesn't
            // make sense to wrap iterators or something like that.
            $keys = iterator_to_array($keys, false);
        }

        try {
            return $this->cacheItemPool->deleteItems($keys);
        } catch (CacheInvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        try {
            return $this->cacheItemPool->hasItem($key);
        } catch (CacheInvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function assertKey(mixed $key): void
    {
        $type = gettype($key);
        if (!in_array($type, ['integer', 'double', 'string'])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid key: Type "%s" is not allowed for keys',
                $type,
            ));
        }

        $key = (string) $key;
        if ($key === '') {
            throw new InvalidArgumentException('Invalid key: Empty string is not allowed');
        }

        $reservedChars = '{}()/\@:';
        $reservedCharsPattern = '/[' . preg_quote($reservedChars, '/') . ']/';

        if (preg_match($reservedCharsPattern, $key)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid key: "%s". The key contains one or more characters reserved for future extension: %s',
                $key,
                $reservedChars,
            ));
        }
    }
}
