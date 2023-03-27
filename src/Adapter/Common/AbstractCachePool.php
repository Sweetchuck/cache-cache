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

use Cache\Adapter\Common\Exception\CacheException;
use Cache\Adapter\Common\Exception\CachePoolException;
use Cache\Adapter\Common\Exception\InvalidArgumentException;
use Cache\TagInterop\TaggableCacheItemInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\SimpleCache\CacheInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
abstract class AbstractCachePool implements PhpCachePool, LoggerAwareInterface, CacheInterface
{
    use LoggerAwareTrait;

    public const SEPARATOR_TAG = '!';

    /**
     * @var array<string, \Cache\TagInterop\TaggableCacheItemInterface>
     */
    protected array $deferred = [];

    /**
     * @param int|null $ttl
     *   Seconds from now.
     *
     * @return bool
     *   Returns true if saved.
     */
    abstract protected function storeItemInCache(PhpCacheItem $item, ?int $ttl): bool;

    /**
     * Fetch an object from the cache implementation.
     *
     * If it is a cache miss, it MUST return [false, null, [], null]
     *
     * @param string $key
     *
     * @return array
     *   With:
     *   0: bool isHit,
     *   1: mixed value,
     *   2: string[] tags,
     *   3: int expirationTimestamp.
     *
     * @phpstan-return cache-item-raw
     */
    abstract protected function fetchObjectFromCache(string $key): array;

    /**
     * Clear all objects from cache.
     *
     * @return bool
     *   Returns false if error.
     */
    abstract protected function clearAllObjectsFromCache(): bool;

    /**
     * Remove one object from cache.
     */
    abstract protected function clearOneObjectFromCache(string $key): bool;

    /**
     * Get an array with all the values in the list named $name.
     *
     * @return array<mixed>
     */
    abstract protected function getList(string $name): array;

    /**
     * Remove the list.
     */
    abstract protected function removeList(string $name): bool;

    /**
     * Add a item key on a list named $name.
     *
     * @return void
     *
     * @todo Return type hint definition. Maybe static.
     */
    abstract protected function appendListItem(string $name, string $key);

    /**
     * Remove an item from the list.
     *
     * @return void
     *
     * @todo Return type hint definition. Maybe static.
     */
    abstract protected function removeListItem(string $name, string $key);

    /**
     * Make sure to commit before we destruct.
     */
    public function __destruct()
    {
        $this->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): TaggableCacheItemInterface
    {
        $this->validateKey($key);
        if (isset($this->deferred[$key])) {
            $item = clone $this->deferred[$key];
            $item->moveTagsToPrevious();

            return $item;
        }

        $func = function () use ($key) {
            try {
                return $this->fetchObjectFromCache($key);
            } catch (\Exception $e) {
                $this->handleException($e, __FUNCTION__);
            }
        };

        return new CacheItem($key, $func);
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-return iterable<string, \Cache\TagInterop\TaggableCacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        try {
            return $this->getItem($key)->isHit();
        } catch (\Exception $e) {
            $this->handleException($e, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        // Clear the deferred items.
        $this->deferred = [];

        try {
            return $this->clearAllObjectsFromCache();
        } catch (\Exception $e) {
            $this->handleException($e, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        try {
            return $this->deleteItems([$key]);
        } catch (\Exception $e) {
            $this->handleException($e, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $deleted = true;
        foreach ($keys as $key) {
            $this->validateKey($key);

            // Delete form deferred.
            unset($this->deferred[$key]);

            // We have to commit here to be able to remove deferred hierarchy items.
            $this->commit();
            $this->preRemoveItem($key);

            if (!$this->clearOneObjectFromCache($key)) {
                $deleted = false;
            }
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        if (!($item instanceof PhpCacheItem)) {
            $e = new InvalidArgumentException(sprintf(
                'Cache items are not transferable between pools. Item MUST implement %s.',
                PhpCacheItem::class,
            ));
            $this->handleException($e, __FUNCTION__);
        }

        $this->removeTagEntries($item);
        $this->saveTags($item);
        $timeToLive = null;
        if (null !== $timestamp = $item->getExpirationTimestamp()) {
            $timeToLive = $timestamp - time();

            if ($timeToLive < 0) {
                return $this->deleteItem($item->getKey());
            }
        }

        try {
            return $this->storeItemInCache($item, $timeToLive);
        } catch (\Exception $e) {
            $this->handleException($e, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-param \Cache\TagInterop\TaggableCacheItemInterface $item
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $saved = true;
        foreach ($this->deferred as $item) {
            if (!$this->save($item)) {
                $saved = false;
            }
        }
        $this->deferred = [];

        return $saved;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return void
     */
    protected function validateKey(string $key)
    {
        if (!isset($key[0])) {
            $e = new InvalidArgumentException('Cache key cannot be an empty string');
            $this->handleException($e, __FUNCTION__);
        }

        $reservedChars = '{}()/\\@:';
        $pattern = '/[' . preg_quote($reservedChars, '/') . ']/';
        if (preg_match($pattern, $key)) {
            $e = new InvalidArgumentException(sprintf(
                'Invalid key: "%s". The key contains one or more characters reserved for future extension: %s',
                $key,
                $reservedChars,
            ));
            $this->handleException($e, __FUNCTION__);
        }
    }

    /**
     * Logs with an arbitrary level if the logger exists.
     *
     * @phpstan-param array<string, mixed> $context
     */
    protected function log(mixed $level, string$message, array $context = []): static
    {
        $this->logger?->log($level, $message, $context);

        return $this;
    }

    /**
     * Log exception and rethrow it.
     *
     * @return never
     *
     * @throws CachePoolException
     */
    private function handleException(\Exception $e, string $function)
    {
        $level = 'alert';
        if ($e instanceof InvalidArgumentException) {
            $level = 'warning';
        }

        $this->log($level, $e->getMessage(), ['exception' => $e]);
        if (!($e instanceof CacheException)) {
            $e = new CachePoolException(
                sprintf('Exception thrown when executing "%s". ', $function),
                0,
                $e,
            );
        }

        throw $e;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags): bool
    {
        $itemIds = [];
        foreach ($tags as $tag) {
            $itemIds = array_merge($itemIds, $this->getList($this->getTagKey($tag)));
        }

        // Remove all items with the tag.
        $success = $this->deleteItems($itemIds);

        if ($success) {
            // Remove the tag list.
            foreach ($tags as $tag) {
                $this->removeList($this->getTagKey($tag));
                $this->getList($this->getTagKey($tag));
            }
        }

        return $success;
    }

    public function invalidateTag(string $tag): bool
    {
        return $this->invalidateTags([$tag]);
    }

    protected function saveTags(PhpCacheItem $item): static
    {
        $tags = $item->getTags();
        foreach ($tags as $tag) {
            $this->appendListItem($this->getTagKey($tag), $item->getKey());
        }

        return $this;
    }

    /**
     * Removes the key form all tag lists. When an item with tags is removed
     * we MUST remove the tags. If we fail to remove the tags a new item with
     * the same key will automatically get the previous tags.
     */
    protected function preRemoveItem(string $key): static
    {
        $item = $this->getItem($key);
        $this->removeTagEntries($item);

        return $this;
    }

    private function removeTagEntries(PhpCacheItem $item): static
    {
        $tags = $item->getPreviousTags();
        foreach ($tags as $tag) {
            $this->removeListItem($this->getTagKey($tag), $item->getKey());
        }

        return $this;
    }

    protected function getTagKey(string $tag): string
    {
        return 'tag'.self::SEPARATOR_TAG.$tag;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->getItem($key);
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
        $item = $this->getItem($key);
        $item->set($value);
        $item->expiresAfter($ttl);

        return $this->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return $this->deleteItem($key);
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

        $items = $this->getItems($keys);

        return $this->generateValues($default, $items);
    }

    /**
     * @phpstan-param iterable<\Psr\Cache\CacheItemInterface> $items
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
     * @phpstan-param iterable<mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $keys = [];
        $arrayValues = [];
        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $key = (string) $key;
            }
            $this->validateKey($key);
            $keys[] = $key;
            $arrayValues[$key] = $value;
        }

        $items = $this->getItems($keys);
        $itemSuccess = true;
        foreach ($items as $key => $item) {
            $item->set($arrayValues[$key]);

            try {
                $item->expiresAfter($ttl);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            $itemSuccess = $itemSuccess && $this->saveDeferred($item);
        }

        return $itemSuccess && $this->commit();
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

        return $this->deleteItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->hasItem($key);
    }
}
