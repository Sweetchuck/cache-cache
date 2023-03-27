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

namespace Cache\Taggable;

use Cache\Taggable\Exception\InvalidArgumentException;
use Cache\TagInterop\TaggableCacheItemInterface;
use Psr\Cache\CacheItemInterface;

/**
 * @internal
 *
 * An adapter for non-taggable cache items, to be used with the cache pool
 * adapter.
 *
 * This adapter stores tags along with the cached value, by storing wrapping
 * the item in an array structure containing both
 *
 * @author Magnus Nordlander <magnus@fervo.se>
 */
class TaggablePSR6ItemAdapter implements TaggableCacheItemInterface
{
    private bool $initialized = false;

    private CacheItemInterface $cacheItem;

    /**
     * @var string[]
     */
    private array $prevTags = [];

    /**
     * @var string[]
     */
    private array $tags = [];

    protected string $reservedChars = '{}()/\\@:';

    protected string $reservedCharsPattern = '';

    /**
     * @param CacheItemInterface $cacheItem
     */
    private function __construct(CacheItemInterface $cacheItem)
    {
        $this->cacheItem = $cacheItem;
        $this->reservedCharsPattern = '/[' . preg_quote($this->reservedChars, '/') . ']/';
    }

    /**
     * @return static
     */
    public static function makeTaggable(CacheItemInterface $cacheItem)
    {
        return new self($cacheItem);
    }

    public function unwrap(): CacheItemInterface
    {
        return $this->cacheItem;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->cacheItem->getKey();
    }

    /**
     * {@inheritdoc}
     */
    public function get(): mixed
    {
        $rawItem = $this->cacheItem->get();

        // If it is a cache item we created.
        if ($this->isItemCreatedHere($rawItem)) {
            return $rawItem['value'];
        }

        // This is an item stored before we used this fake cache.
        return $rawItem;
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
    public function set(mixed $value): static
    {
        $this->initializeTags();

        $this->cacheItem->set([
            'value' => $value,
            'tags'  => $this->tags,
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviousTags(): array
    {
        $this->initializeTags();

        return $this->prevTags;
    }

    /**
     * @return string[]
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

        return $this->tag($tags);
    }

    /**
     * @param string|\Stringable|iterable<string|\Stringable> $tags
     */
    private function tag(string|\Stringable|iterable $tags): static
    {
        if (!is_iterable($tags)) {
            $tags = [$tags];
        }

        $this->initializeTags();

        foreach ($tags as $tag) {
            if (!is_string($tag) && !($tag instanceof \Stringable)) {
                throw new InvalidArgumentException(sprintf(
                    'Cache tag must be string, "%s" given',
                    is_object($tag) ? get_class($tag) : gettype($tag),
                ));
            }

            $string = (string) $tag;

            if (isset($this->tags[$string])) {
                continue;
            }

            if (!strlen($string)) {
                throw new InvalidArgumentException('Cache tag length must be greater than zero');
            }

            if (preg_match($this->reservedCharsPattern, $string) === 1) {
                throw new InvalidArgumentException(sprintf(
                    'Cache tag "%s" contains reserved characters %s',
                    $string,
                    $this->reservedChars,
                ));
            }

            $this->tags[$string] = $string;
        }

        $this->updateTags();

        return $this;
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

    private function updateTags(): static
    {
        $this->cacheItem->set([
            'value' => $this->get(),
            'tags'  => $this->tags,
        ]);

        return $this;
    }

    private function initializeTags(): static
    {
        if ($this->initialized) {
            return $this;
        }

        if ($this->cacheItem->isHit()) {
            $rawItem = $this->cacheItem->get();

            if ($this->isItemCreatedHere($rawItem)) {
                $this->prevTags = $rawItem['tags'];
            }
        }

        $this->initialized = true;

        return $this;
    }

    /**
     * Verify that the raw data is a cache item created by this class.
     */
    private function isItemCreatedHere(mixed $rawItem): bool
    {
        return is_array($rawItem)
            && array_key_exists('value', $rawItem)
            && array_key_exists('tags', $rawItem)
            && count($rawItem) === 2;
    }
}
