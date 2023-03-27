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

namespace Cache\Adapter\MongoDB;

use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\JsonBinaryArmoring;
use Cache\Adapter\Common\PhpCacheItem;
use Cache\Adapter\Common\TagSupportWithArray;
use MongoDB\Collection;
use MongoDB\Driver\Manager;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Magnus Nordlander
 */
class MongoDBCachePool extends AbstractCachePool
{
    use JsonBinaryArmoring;
    use TagSupportWithArray;

    private Collection $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public static function createCollection(Manager $manager, string $database, string $collectionName): Collection
    {
        $collection = new Collection($manager, $database, $collectionName);
        $collection->createIndex(['expireAt' => 1], ['expireAfterSeconds' => 0]);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchObjectFromCache(string $key): array
    {
        $object = $this->collection->findOne(['_id' => $key]);

        if (!$object || !isset($object->data)) {
            return [false, null, [], null];
        }

        if (isset($object->expiresAt)
            && $object->expiresAt < time()
        ) {
            return [false, null, [], null];
        }

        return [
            true,
            $this->thawValue($object->data),
            $this->thawValue($object->tags),
            $object->expirationTimestamp,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function clearAllObjectsFromCache(): bool
    {
        $this->collection->deleteMany([]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function clearOneObjectFromCache(string $key): bool
    {
        $this->collection->deleteOne(['_id' => $key]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function storeItemInCache(PhpCacheItem $item, ?int $ttl): bool
    {
        $object = [
            '_id'                 => $item->getKey(),
            'data'                => $this->freezeValue($item->get()),
            'tags'                => $this->freezeValue($item->getTags()),
            'expirationTimestamp' => $item->getExpirationTimestamp(),
        ];

        if ($ttl) {
            $object['expiresAt'] = time() + $ttl;
        }

        $this->collection->updateOne(['_id' => $item->getKey()], ['$set' => $object], ['upsert' => true]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectValue(string $name): mixed
    {
        $object = $this->collection->findOne(['_id' => $name]);
        if (!$object || !isset($object->data)) {
            return null;
        }

        return $this->thawValue($object->data);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function setDirectValue(string $name, mixed $value)
    {
        $object = [
            '_id'  => $name,
            'data' => $this->freezeValue($value),
        ];

        $this->collection->updateOne(['_id' => $name], ['$set' => $object], ['upsert' => true]);
    }

    private function freezeValue(mixed $value): string
    {
        return static::jsonArmor(serialize($value));
    }

    private function thawValue(string $value): mixed
    {
        return unserialize(static::jsonDeArmor($value));
    }
}
