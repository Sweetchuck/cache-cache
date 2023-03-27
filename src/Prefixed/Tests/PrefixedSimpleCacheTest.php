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

namespace Cache\Prefixed\Tests;

use Cache\Prefixed\PrefixedSimpleCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * Description of PrefixedSimpleCacheTest.
 *
 * @author ndobromirov
 */
class PrefixedSimpleCacheTest extends TestCase
{
    /**
     * @param string $method
     *   Method name to mock.
     * @param array<mixed> $arguments
     *   List of expected arguments.
     *
     * @return \Psr\SimpleCache\CacheInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getCacheStub(string $method, array $arguments, mixed $result)
    {
        $stub = $this
            ->getMockBuilder(CacheInterface::class)
            ->onlyMethods(['get', 'set', 'delete', 'clear', 'getMultiple', 'setMultiple', 'deleteMultiple', 'has'])
            ->getMock();

        $stub
            ->expects($this->once())
            ->method($method)
            ->with(...$arguments)
            ->willReturn($result);

        return $stub;
    }

    public function testGet(): void
    {
        $prefix = 'ns';
        $key    = 'key';
        $result = true;

        $stub = $this->getCacheStub('get', [$prefix.$key], $result);
        $pool = new PrefixedSimpleCache($stub, $prefix);

        static::assertEquals($result, $pool->get($key));
    }

    public function testSet(): void
    {
        $prefix = 'ns';
        $key    = 'key';
        $value  = 'value';
        $result = true;

        $stub = $this->getCacheStub('set', [$prefix.$key, $value], $result);
        $pool = new PrefixedSimpleCache($stub, $prefix);

        static::assertEquals($result, $pool->set($key, $value));
    }

    public function testDelete(): void
    {
        $prefix = 'ns';
        $key    = 'key';
        $result = true;

        $stub = $this->getCacheStub('delete', [$prefix.$key], $result);
        $pool = new PrefixedSimpleCache($stub, $prefix);

        static::assertEquals($result, $pool->delete($key));
    }

    public function testClear(): void
    {
        $prefix = 'ns';
        $result = true;

        $stub = $this->getCacheStub('clear', [], $result);
        $pool = new PrefixedSimpleCache($stub, $prefix);

        static::assertEquals($result, $pool->clear());
    }

    public function testGetMultiple(): void
    {
        $prefix              = 'ns';
        list($key1, $value1) = ['key1', 1];
        list($key2, $value2) = ['key2', 2];

        $stub = $this->getCacheStub(
            'getMultiple',
            [[$prefix.$key1, $prefix.$key2]],
            [
                $prefix.$key1 => $value1,
                $prefix.$key2 => $value2,
            ],
        );
        $pool = new PrefixedSimpleCache($stub, $prefix);

        static::assertEquals(
            [$key1 => $value1, $key2 => $value2],
            $pool->getMultiple([$key1, $key2]),
        );
    }

    public function testSetMultiple(): void
    {
        $prefix              = 'ns';
        list($key1, $value1) = ['key1', 1];
        list($key2, $value2) = ['key2', 2];
        $result              = true;

        $stub = $this->getCacheStub('setMultiple', [[$prefix.$key1 => $value1, $prefix.$key2 => $value2]], $result);
        $pool = new PrefixedSimpleCache($stub, $prefix);

        static::assertEquals($result, $pool->setMultiple([$key1 => $value1, $key2 => $value2]));
    }

    public function testDeleteMultiple(): void
    {
        $prefix            = 'ns';
        list($key1, $key2) = ['key1', 'key2'];
        $result            = true;

        $stub = $this->getCacheStub('deleteMultiple', [[$prefix.$key1, $prefix.$key2]], $result);
        $pool = new PrefixedSimpleCache($stub, $prefix);

        static::assertEquals($result, $pool->deleteMultiple([$key1, $key2]));
    }

    public function testHas(): void
    {
        $prefix = 'ns';
        $key    = 'key';
        $result = true;

        $stub = $this->getCacheStub('has', [$prefix.$key], $result);
        $pool = new PrefixedSimpleCache($stub, $prefix);

        static::assertEquals($result, $pool->has($key));
    }
}
