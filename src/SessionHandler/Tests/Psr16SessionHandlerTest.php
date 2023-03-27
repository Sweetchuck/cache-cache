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
use Cache\SessionHandler\Psr16SessionHandler;

/**
 * @author Daniel Bannert <d.bannert@anolilab.de>s
 *
 * @property \Cache\SessionHandler\Psr16SessionHandler $handler
 */
class Psr16SessionHandlerTest extends AbstractSessionHandlerTestBase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Psr\SimpleCache\CacheInterface
     */
    private $psr16;

    protected function setUp(): void
    {
        parent::setUp();

        $this->psr16 = $this
            ->getMockBuilder(ArrayCachePool::class)
            ->onlyMethods(['get', 'set', 'delete'])
            ->getMock();

        $this->handler = new Psr16SessionHandler(
            $this->psr16,
            [
                'prefix' => self::PREFIX,
                'ttl' => self::TTL,
            ],
        );
    }

    public function testReadMiss(): void
    {
        $this
            ->psr16
            ->expects(static::once())
            ->method('get')
            ->willReturn('');

        static::assertEquals('', $this->handler->read('foo'));
    }

    public function testReadHit(): void
    {
        $this
            ->psr16
            ->expects(static::once())
            ->method('get')
            ->with(self::PREFIX.'foo', '')
            ->willReturn('bar');

        static::assertEquals('bar', $this->handler->read('foo'));
    }

    public function testWrite(): void
    {
        $this
            ->psr16
            ->expects(static::once())
            ->method('set')
            ->with(self::PREFIX.'foo', 'session value', self::TTL)
            ->willReturn(true);

        static::assertTrue($this->handler->write('foo', 'session value'));
    }

    public function testDestroy(): void
    {
        $this
            ->psr16
            ->expects(static::once())
            ->method('delete')
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
            new Psr16SessionHandler($this->psr16, $options);

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
        $this
            ->psr16
            ->expects(static::once())
            ->method('get')
            ->with(self::PREFIX.'foo')
            ->willReturn('session value');
        $this
            ->psr16
            ->expects(static::once())
            ->method('set')
            ->with(
                self::PREFIX.'foo',
                'session value',
                100,
            )
            ->willReturn(true);

        static::assertTrue($this->handler->updateTimestamp('foo', 'session value'));
    }

    public function testValidateId(): void
    {
        $this
            ->psr16
            ->expects(static::once())
            ->method('get')
            ->with(self::PREFIX.'foo', '')
            ->willReturn('bar');

        static::assertTrue($this->handler->validateId('foo'));
    }
}
