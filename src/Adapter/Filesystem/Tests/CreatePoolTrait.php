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

namespace Cache\Adapter\Filesystem\Tests;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\SimpleCache\CacheInterface;

trait CreatePoolTrait
{
    protected ?Filesystem $filesystem = null;

    public function createCachePool(): FilesystemCachePool
    {
        return new FilesystemCachePool($this->getFilesystem());
    }

    public function createSimpleCache(): CacheInterface
    {
        return $this->createCachePool();
    }

    protected function getFilesystem(): Filesystem
    {
        if ($this->filesystem === null) {
            $this->filesystem = new Filesystem(new Local(__DIR__.'/../tmp/'.rand(1, 100000)));
        }

        return $this->filesystem;
    }

    public static function tearDownAfterClassFilesystem(): void
    {
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->remove(__DIR__ . '/../tmp/');
    }
}
