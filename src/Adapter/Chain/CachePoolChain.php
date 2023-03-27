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

namespace Cache\Adapter\Chain;

use Cache\Adapter\Chain\Exception\NoPoolAvailableException;
use Cache\Adapter\Common\Exception\CachePoolException;
use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CachePoolChain implements CacheItemPoolInterface, TaggableCacheItemPoolInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array<\Cache\TagInterop\TaggableCacheItemPoolInterface>
     */
    private array $pools;

    /**
     * @phpstan-var cache-pool-chain-options-full
     */
    private array $options;

    /**
     * @param array<\Cache\TagInterop\TaggableCacheItemPoolInterface> $pools
     *
     * @phpstan-param cache-pool-chain-options-lazy $options
     */
    public function __construct(array $pools, array $options = [])
    {
        $this->pools = $pools;

        if (!isset($options['skip_on_failure'])) {
            $options['skip_on_failure'] = false;
        }

        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): TaggableCacheItemInterface
    {
        $found     = false;
        $result    = null;
        $item      = null;
        $needsSave = [];

        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                $item = $pool->getItem($key);

                if ($item->isHit()) {
                    $found  = true;
                    $result = $item;
                    break;
                }

                $needsSave[] = $pool;
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        if ($found) {
            foreach ($needsSave as $pool) {
                $pool->save($result);
            }

            $item = $result;
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     *
     * @phpstan-return array<string, \Psr\Cache\CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        $hits          = [];
        $loadedItems   = [];
        $notFoundItems = [];
        $keysCount     = count($keys);
        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                $items = $pool->getItems($keys);

                foreach ($items as $item) {
                    if ($item->isHit()) {
                        $hits[$item->getKey()] = $item;
                        unset($keys[array_search($item->getKey(), $keys)]);
                    } else {
                        $notFoundItems[$poolKey][$item->getKey()] = $item->getKey();
                    }
                    $loadedItems[$item->getKey()] = $item;
                }
                if (count($hits) === $keysCount) {
                    break;
                }
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        if (!empty($hits) && !empty($notFoundItems)) {
            foreach ($notFoundItems as $poolKey => $itemKeys) {
                try {
                    $pool  = $this->getPools()[$poolKey];
                    $found = false;
                    foreach ($itemKeys as $itemKey) {
                        if (!empty($hits[$itemKey])) {
                            $found = true;
                            $pool->saveDeferred($hits[$itemKey]);
                        }
                    }
                    if ($found) {
                        $pool->commit();
                    }
                } catch (CachePoolException $e) {
                    $this->handleException($poolKey, __FUNCTION__, $e);
                }
            }
        }

        return array_merge($loadedItems, $hits);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                if ($pool->hasItem($key)) {
                    return true;
                }
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $result = true;
        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                $result = $result && $pool->clear();
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        $result = true;
        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                $result = $result && $pool->deleteItem($key);
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $result = true;
        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                $result = $result && $pool->deleteItems($keys);
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        $result = true;
        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                $result = $pool->save($item) && $result;
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $result = true;
        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                $result = $pool->saveDeferred($item) && $result;
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $result = true;
        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                $result = $pool->commit() && $result;
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTag(string $tag): bool
    {
        return $this->invalidateTags([$tag]);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags): bool
    {
        $result = true;
        foreach ($this->getPools() as $poolKey => $pool) {
            try {
                $result = $pool->invalidateTags($tags) && $result;
            } catch (CachePoolException $e) {
                $this->handleException($poolKey, __FUNCTION__, $e);
            }
        }

        return $result;
    }

    /**
     * Logs with an arbitrary level if the logger exists.
     *
     * @phpstan-param array<string, mixed> $context
     */
    protected function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $this->logger?->log($level, $message, $context);
    }

    /**
     * @return array<\Cache\TagInterop\TaggableCacheItemPoolInterface>
     */
    protected function getPools(): array
    {
        if (!$this->pools) {
            throw new NoPoolAvailableException('No valid cache pool available for the chain.');
        }

        return $this->pools;
    }

    /**
     * @throws \Cache\Adapter\Chain\Exception\PoolFailedException
     */
    private function handleException(string $poolKey, string $operation, CachePoolException $exception): void
    {
        if (!$this->options['skip_on_failure']) {
            throw $exception;
        }

        $this->log(
            'warning',
            sprintf(
                'Removing pool "%s" from chain because it threw an exception when executing "%s"',
                $poolKey,
                $operation,
            ),
            ['exception' => $exception],
        );

        unset($this->pools[$poolKey]);
    }
}
