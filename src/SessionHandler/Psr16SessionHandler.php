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

use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * @author Daniel Bannert <d.bannert@anolilab.de>
 */
class Psr16SessionHandler extends AbstractSessionHandler
{
    private CacheInterface $cache;

    /**
     * Time to live in seconds.
     */
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
    public function __construct(CacheInterface $cache, array $options = [])
    {
        $this->cache = $cache;

        if ($diff = array_diff(array_keys($options), ['prefix', 'ttl'])) {
            throw new \InvalidArgumentException(sprintf(
                'The following options are not supported "%s"',
                implode(', ', $diff),
            ));
        }

        $this->ttl = (int) ($options['ttl'] ?? 86400);
        $this->prefix = $options['prefix'] ?? 'psr16ses_';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        $value = $this->cache->get($this->prefix.$id);

        if ($value === null) {
            return false;
        }

        return $this->cache->set(
            $this->prefix.$id,
            $value,
            $this->ttl,
        );
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function doRead(string $id): string
    {
        return $this->cache->get($this->prefix.$id, '');
    }

    protected function doWrite(string $sessionId, string $data): bool
    {
        return $this->cache->set($this->prefix.$sessionId, $data, $this->ttl);
    }

    protected function doDestroy(string $sessionId): bool
    {
        return $this->cache->delete($this->prefix.$sessionId);
    }
}
