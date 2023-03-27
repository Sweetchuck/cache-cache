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

use Cache\Adapter\Common\Exception\InvalidArgumentException;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CacheItem implements PhpCacheItem
{
    /**
     * @var array<string>
     */
    private array $prevTags = [];

    /**
     * @var array<string>
     */
    private array $tags = [];

    private ?\Closure $callable = null;

    private string $key = '';

    private bool $hasValue = false;

    private mixed $value = null;

    /**
     * The expiration timestamp is the source of truth. This is the UTC timestamp
     * when the cache item expire. A value of zero means it never expires. A nullvalue
     * means that no expiration is set.
     */
    private ?int $expirationTimestamp = null;

    protected string $reservedChars = '{}()/\\@:';

    protected string $reservedCharsPattern = '';

    /**
     * @param null|bool|\Closure $callable
     *   Value for ::$callable or ::$hasValue.
     */
    public function __construct(string $key, null|bool|\Closure $callable = null, mixed $value = null)
    {
        $this->key = $key;
        $this->reservedCharsPattern = '/[' . preg_quote($this->reservedChars, '/') . ']/';

        if ($callable === true) {
            $this->hasValue = true;
            $this->value = $value;
        } elseif ($callable !== false) {
            $this->callable = $callable;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function set(mixed $value): static
    {
        $this->value    = $value;
        $this->hasValue = true;
        $this->callable = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): mixed
    {
        return $this->isHit() ?
            $this->value
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        $this->initialize();

        if (!$this->hasValue) {
            return false;
        }

        if ($this->expirationTimestamp !== null) {
            return $this->expirationTimestamp > time();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpirationTimestamp(): ?int
    {
        return $this->expirationTimestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->expirationTimestamp = $expiration?->getTimestamp();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if (is_int($time)) {
            $this->expirationTimestamp = time() + $time;
        } elseif ($time instanceof \DateInterval) {
            $date = new \DateTime();
            $date->add($time);
            $this->expirationTimestamp = $date->getTimestamp();
        } else {
            $this->expirationTimestamp = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviousTags(): array
    {
        $this->initialize();

        return $this->prevTags;
    }

    /**
     * {@inheritdoc}
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * {@inheritdoc}
     */
    public function setTags(iterable $tags): static
    {
        $this->tags = [];
        $this->tag($tags);

        return $this;
    }

    /**
     * Adds a tag to a cache item.
     *
     * @phpstan-param string|iterable<string> $tags A tag or array of tags
     *
     * @throws InvalidArgumentException When $tag is not valid.
     */
    private function tag(string|iterable $tags): static
    {
        $this->initialize();

        if (is_string($tags)) {
            $tags = [$tags];
        }

        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                throw new InvalidArgumentException(sprintf(
                    'Cache tag must be string, "%s" given',
                    is_object($tag) ? get_class($tag) : gettype($tag),
                ));
            }

            if (isset($this->tags[$tag])) {
                continue;
            }

            if (strlen($tag) === 0) {
                throw new InvalidArgumentException('Cache tag length must be greater than zero');
            }

            if (preg_match($this->reservedCharsPattern, $tag) === 1) {
                throw new InvalidArgumentException(sprintf(
                    'Cache tag "%s" contains reserved characters %s',
                    $tag,
                    $this->reservedChars,
                ));
            }

            $this->tags[$tag] = $tag;
        }

        return $this;
    }

    /**
     * If callable is not null, execute it an populate this object with values.
     */
    private function initialize(): void
    {
        if ($this->callable) {
            // $func will be $adapter->fetchObjectFromCache();
            $func = $this->callable;
            $result = $func();
            $this->hasValue = $result[0];
            $this->value = $result[1];
            $this->prevTags = $result[2] ?? [];
            $this->expirationTimestamp = null;

            if (isset($result[3]) && is_int($result[3])) {
                $this->expirationTimestamp = $result[3];
            }

            $this->callable = null;
        }
    }

    /**
     * @internal This function should never be used and considered private.
     *
     * Move tags from $tags to $prevTags
     *
     * @return void
     */
    public function moveTagsToPrevious()
    {
        $this->prevTags = $this->tags;
        $this->tags = [];
    }
}
