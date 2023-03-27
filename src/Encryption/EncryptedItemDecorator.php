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

namespace Cache\Encryption;

use Cache\Adapter\Common\JsonBinaryArmoring;
use Cache\TagInterop\TaggableCacheItemInterface;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

/**
 * Encrypt and Decrypt all the stored items.
 *
 * @author Daniel Bannert <d.bannert@anolilab.de>
 */
class EncryptedItemDecorator implements TaggableCacheItemInterface
{
    use JsonBinaryArmoring;

    /**
     * The cacheItem should always contain encrypted data.
     */
    private TaggableCacheItemInterface $cacheItem;

    private Key $key;

    public function __construct(TaggableCacheItemInterface $cacheItem, Key $key)
    {
        $this->cacheItem = $cacheItem;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->cacheItem->getKey();
    }

    public function getCacheItem(): TaggableCacheItemInterface
    {
        return $this->cacheItem;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function set(mixed $value): static
    {
        $type = gettype($value);

        if ($type === 'object' || $type === 'array') {
            $value = serialize($value);
        }

        $json = json_encode([
            'type' => $type,
            'value' => is_string($value) ?
                static::jsonArmor($value)
                : $value,
        ]);

        if (!$json) {
            // @todo Error handling.
            return $this;
        }

        $this->cacheItem->set(Crypto::encrypt($json, $this->key));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): mixed
    {
        if (!$this->isHit()) {
            return null;
        }

        $item = json_decode(
            Crypto::decrypt($this->cacheItem->get(), $this->key),
            true,
        );

        return $this->transform($item);
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        return $this->cacheItem->isHit();
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->cacheItem->expiresAt($expiration);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter(int|\DateInterval|null $time): static
    {
        $this->cacheItem->expiresAfter($time);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviousTags(): array
    {
        return $this->cacheItem->getPreviousTags();
    }

    /**
     * {@inheritdoc}
     */
    public function setTags(iterable $tags): static
    {
        $this->cacheItem->setTags($tags);

        return $this;
    }

    /**
     * Creating a copy of the original CacheItemInterface object.
     */
    public function __clone()
    {
        $this->cacheItem = clone $this->cacheItem;
    }

    /**
     * Transform value back to it original type.
     *
     * @phpstan-param cache-encrypted-item-raw $item
     */
    private function transform(array $item): mixed
    {
        $value = is_string($item['value']) ?
            static::jsonDeArmor($item['value'])
            : $item['value'];

        if ($item['type'] === 'object' || $item['type'] === 'array') {
            return unserialize($value);
        }

        settype($value, $item['type']);

        return $value;
    }
}
