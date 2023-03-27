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

use PHPUnit\Framework\TestCase;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Daniel Bannert <d.bannert@anolilab.de>
 */
abstract class AbstractSessionHandlerTestBase extends TestCase
{
    public const TTL = 100;

    public const PREFIX = 'pre';

    protected \SessionHandlerInterface $handler;

    public function testOpen(): void
    {
        static::assertTrue($this->handler->open('foo', 'bar'));
    }

    public function testClose(): void
    {
        static::assertTrue($this->handler->close());
    }

    public function testGc(): void
    {
        static::assertIsInt($this->handler->gc(4711));
    }
}
