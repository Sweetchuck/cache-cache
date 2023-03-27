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

namespace Cache\SessionHandler\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\SessionHandler\Psr6SessionHandler;
use Psr\Cache\CacheItemInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Daniel Bannert <d.bannert@anolilab.de>s
 *
 * @property \Cache\SessionHandler\Psr6SessionHandler $handler
 */
class Psr6SessionHandlerTest extends AbstractSessionHandlerTestBase
{

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Psr\Cache\CacheItemPoolInterface
     */
    private $psr6;

    protected function setUp(): void
    {
        parent::setUp();

        $this->psr6 = $this
            ->getMockBuilder(ArrayCachePool::class)
            ->onlyMethods(['getItem', 'deleteItem', 'save'])
            ->getMock();

        $this->handler = new Psr6SessionHandler(
            $this->psr6,
            [
                'prefix' => self::PREFIX,
                'ttl' => self::TTL,
            ],
        );
    }

    public function testReadMiss(): void
    {
        $item = $this->getItemMock();
        $item
            ->expects(static::once())
            ->method('isHit')
            ->willReturn(false);
        $this
            ->psr6
            ->expects(static::once())
            ->method('getItem')
            ->willReturn($item);

        static::assertEquals('', $this->handler->read('foo'));
    }

    public function testReadHit(): void
    {
        $item = $this->getItemMock();
        $item
            ->expects(static::once())
            ->method('isHit')
            ->willReturn(true);
        $item
            ->expects(static::once())
            ->method('get')
            ->willReturn('bar');
        $this
            ->psr6
            ->expects(static::once())
            ->method('getItem')
            ->willReturn($item);

        static::assertEquals('bar', $this->handler->read('foo'));
    }

    public function testWrite(): void
    {
        $item = $this->getItemMock();
        $item
            ->expects(static::once())
            ->method('set')
            ->with('session value')
            ->willReturnSelf();
        $item
            ->expects(static::once())
            ->method('expiresAfter')
            ->with(self::TTL)
            ->willReturnSelf();
        $this
            ->psr6
            ->expects(static::once())
            ->method('getItem')
            ->with(self::PREFIX.'foo')
            ->willReturn($item);
        $this
            ->psr6
            ->expects(static::once())
            ->method('save')
            ->with($item)
            ->willReturn(true);

        static::assertTrue($this->handler->write('foo', 'session value'));
    }

    public function testDestroy(): void
    {
        $this
            ->psr6
            ->expects(static::once())
            ->method('deleteItem')
            ->with(self::PREFIX.'foo')
            ->willReturn(true);
        static::assertTrue($this->handler->destroy('foo'));
    }

    /**
     * @dataProvider getOptionFixtures
     *
     * @phpstan-param psr6-session-options $options
     */
    public function testSupportedOptions(array $options, bool $supported): void
    {
        try {
            new Psr6SessionHandler($this->psr6, $options);

            static::assertTrue($supported);
        } catch (\InvalidArgumentException $e) {
            static::assertFalse($supported);
        }
    }

    /**
     * @phpstan-return array<mixed>
     */
    public static function getOptionFixtures(): array
    {
        return [
            [['prefix' => 'session'], true],
            [['ttl' => 100], true],
            [['prefix' => 'session', 'ttl' => 200], true],
            [['ttl' => 100, 'foo' => 'bar'], false],
        ];
    }

    public function testUpdateTimestamp(): void
    {
        $item = $this->getItemMock();
        $item
            ->expects(static::once())
            ->method('set')
            ->with('session value')
            ->willReturnSelf();
        $item
            ->expects(static::once())
            ->method('expiresAfter')
            ->with(self::TTL)
            ->willReturnSelf();
        $item
            ->expects(static::once())
            ->method('expiresAt')
            ->with(\DateTime::createFromFormat('U', (string) (\time() + self::TTL)))
            ->willReturnSelf();
        $this
            ->psr6
            ->expects(static::exactly(2))
            ->method('getItem')
            ->with(self::PREFIX.'foo')
            ->willReturn($item);
        $this
            ->psr6
            ->expects(static::exactly(2))
            ->method('save')
            ->with($item)
            ->willReturn(true);

        $this->handler->write('foo', 'session value');

        static::assertTrue($this->handler->updateTimestamp('foo', 'session value'));
    }

    public function testValidateId(): void
    {
        $item = $this->getItemMock();
        $item
            ->expects(static::once())
            ->method('isHit')
            ->willReturn(true);
        $item
            ->expects(static::once())
            ->method('get')
            ->willReturn('bar');
        $this
            ->psr6
            ->expects(static::once())
            ->method('getItem')
            ->willReturn($item);

        static::assertTrue($this->handler->validateId('foo'));
    }

    /**
     * @return \Cache\Adapter\Common\CacheItem&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getItemMock()
    {
        return $this
            ->getMockBuilder(\Cache\Adapter\Common\CacheItem::class)
            ->setConstructorArgs(['my-key-01'])
            ->onlyMethods(['isHit', 'getKey', 'get', 'set', 'expiresAt', 'expiresAfter'])
            ->getMock();
    }
}
