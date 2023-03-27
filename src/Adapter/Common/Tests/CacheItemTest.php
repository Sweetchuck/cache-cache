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

namespace Cache\Adapter\Common\Tests;

use Cache\Adapter\Common\CacheItem;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;

class CacheItemTest extends TestCase
{
    public function testConstructor(): void
    {
        $item = new CacheItem('test_key');

        static::assertInstanceOf(CacheItem::class, $item);
        static::assertInstanceOf(CacheItemInterface::class, $item);
    }

    public function testGetKey(): void
    {
        $item = new CacheItem('test_key');
        static::assertEquals('test_key', $item->getKey());
    }

    public function testSet(): void
    {
        $item = new CacheItem('test_key');

        $ref       = new \ReflectionObject($item);
        $valueProp = $ref->getProperty('value');
        $valueProp->setAccessible(true);
        $hasValueProp = $ref->getProperty('hasValue');
        $hasValueProp->setAccessible(true);

        static::assertEquals(null, $valueProp->getValue($item));
        static::assertFalse($hasValueProp->getValue($item));

        $item->set('value');

        static::assertEquals('value', $valueProp->getValue($item));
        static::assertTrue($hasValueProp->getValue($item));
    }

    public function testGet(): void
    {
        $item = new CacheItem('test_key');
        static::assertNull($item->get());

        $item->set('test');
        static::assertEquals('test', $item->get());
    }

    public function testHit(): void
    {
        $item = new CacheItem('test_key', true, 'value');
        static::assertTrue($item->isHit());

        $item = new CacheItem('test_key', false, 'value');
        static::assertFalse($item->isHit());

        $closure = function () {
            return [true, 'value', []];
        };
        $item = new CacheItem('test_key', $closure);
        static::assertTrue($item->isHit());

        $closure = function () {
            return [false, null, []];
        };
        $item = new CacheItem('test_key', $closure);
        static::assertFalse($item->isHit());
    }

    public function testGetExpirationTimestamp(): void
    {
        $item = new CacheItem('test_key');

        static::assertNull($item->getExpirationTimestamp());

        $timestamp = time();

        $ref  = new \ReflectionObject($item);
        $prop = $ref->getProperty('expirationTimestamp');
        $prop->setAccessible(true);
        $prop->setValue($item, $timestamp);

        static::assertEquals($timestamp, $item->getExpirationTimestamp());
    }

    public function testExpiresAt(): void
    {
        $item = new CacheItem('test_key');

        static::assertNull($item->getExpirationTimestamp());

        $expires = new \DateTime('+1 second');
        $item->expiresAt($expires);

        static::assertEquals($expires->getTimestamp(), $item->getExpirationTimestamp());
    }

    public function testExpiresAfter(): void
    {
        $item = new CacheItem('test_key');

        static::assertNull($item->getExpirationTimestamp());

        $item->expiresAfter(null);

        $item->expiresAfter(new \DateInterval('PT1S'));
        static::assertEquals((new \DateTime('+1 second'))->getTimestamp(), $item->getExpirationTimestamp());

        $item->expiresAfter(1);
        static::assertEquals((new \DateTime('+1 second'))->getTimestamp(), $item->getExpirationTimestamp());
    }
}
