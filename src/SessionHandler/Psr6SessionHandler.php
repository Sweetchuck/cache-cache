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

namespace Cache\SessionHandler;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Daniel Bannert <d.bannert@anolilab.de>
 */
class Psr6SessionHandler extends AbstractSessionHandler
{
    private CacheItemPoolInterface $cache;

    private int $ttl;

    /**
     * Key prefix for shared environments.
     */
    private string $prefix;

    /**
     * @phpstan-param psr6-session-options $options
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(CacheItemPoolInterface $cache, array $options = [])
    {
        $this->cache = $cache;

        if ($diff = array_diff(array_keys($options), ['prefix', 'ttl'])) {
            throw new \InvalidArgumentException(sprintf(
                'The following options are not supported "%s"',
                implode(', ', $diff),
            ));
        }

        $options += [
            'ttl' => 86400,
            'prefix' => 'psr6ses_',
        ];

        $this->ttl = $options['ttl'];
        $this->prefix = $options['prefix'];
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        $item = $this->getCacheItem($id);
        $expiration = \DateTime::createFromFormat(
            'U',
            (string) (\time() + $this->ttl),
        );
        $item->expiresAt($expiration ?: null);

        return $this->cache->save($item);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function doRead(string $id): string
    {
        $item = $this->getCacheItem($id);

        return $item->isHit() ?
            $item->get()
            : '';
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function doWrite(string $sessionId, string $data): bool
    {
        $item = $this->getCacheItem($sessionId);
        $item
            ->set($data)
            ->expiresAfter($this->ttl);

        return $this->cache->save($item);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function doDestroy(string $sessionId): bool
    {
        return $this->cache->deleteItem($this->prefix.$sessionId);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getCacheItem(string $sessionId): CacheItemInterface
    {
        return $this->cache->getItem($this->prefix.$sessionId);
    }
}
