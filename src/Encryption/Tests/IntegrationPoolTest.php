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

namespace Cache\Encryption\Tests;

use Cache\Adapter\Common\CacheItem;
use Cache\Adapter\Common\Exception\InvalidArgumentException;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cache\Encryption\EncryptedCachePool;
use Cache\IntegrationTests\CachePoolTest;
use Defuse\Crypto\Key;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\Cache\CacheItemPoolInterface;

class IntegrationPoolTest extends CachePoolTest
{

    /**
     * {@inheritdoc}
     *
     * @phpstan-var array<string, string>
     */
    protected array $skippedTests = [
        'testBasicUsageWithLongKey' => 'Long keys are not supported.',
    ];

    private ?Filesystem $filesystem = null;

    /**
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     *
     * @return \Cache\Encryption\EncryptedCachePool|\Psr\Cache\CacheItemPoolInterface
     */
    public function createCachePool(): CacheItemPoolInterface
    {
        return new EncryptedCachePool(
            new FilesystemCachePool($this->getFilesystem()),
            Key::loadFromAsciiSafeString(implode('', [
                'def000007c57b06c65b0df4bcac939924e',
                '42605d8d76e1462b619318bf94107c28db',
                '30c5394b4242db5e45563e1226cffcdff8',
                '123fa214ea1fcc4aa10b0ddb1b4a587b7e',
            ])),
        );
    }

    public function testSaveToThrowAInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $pool = $this->createCachePool();

        $pool->save(new CacheItem('save_valid_exceptiond'));
    }

    public function testSaveDeferredToThrowAInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $pool = $this->createCachePool();

        $pool->saveDeferred(new CacheItem('save_valid_exceptiond'));
    }

    private function getFilesystem(): Filesystem
    {
        if ($this->filesystem === null) {
            $this->filesystem = new Filesystem(new Local(__DIR__.'/../tmp/'.rand(1, 100000)));
        }

        return $this->filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->remove([__DIR__ . '/../tmp/']);
    }
}
