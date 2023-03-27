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

namespace Cache\SessionHandler;

/**
 * @author Daniel Bannert <d.bannert@anolilab.de>
 *
 * ported from https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpFoundation/Session/Storage/Handler/AbstractSessionHandler.php
 */
abstract class AbstractSessionHandler implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    private ?string $prefetchId = null;

    private ?string $prefetchData = null;

    private ?string $newSessionId = null;

    private ?string $igbinaryEmptyData = null;

    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function validateId(string $id): bool
    {
        $this->prefetchData = $this->read($id) ?: null;
        $this->prefetchId = $id;

        return $this->prefetchData !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $id): string|false
    {
        if ($this->prefetchId !== null) {
            $prefetchId   = $this->prefetchId;
            $prefetchData = $this->prefetchData;

            $this->prefetchId = $this->prefetchData = null;

            if ($prefetchId === $id || '' === $prefetchData) {
                $this->newSessionId = '' === $prefetchData ? $id : null;

                return $prefetchData;
            }
        }

        $data = $this->doRead($id);
        $this->newSessionId = '' === $data ? $id : null;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $id, string $data): bool
    {
        if (\PHP_VERSION_ID < 70000 && $this->prefetchData) {
            $readData           = $this->prefetchData;
            $this->prefetchData = null;

            if ($readData === $data) {
                return $this->updateTimestamp($id, $data);
            }
        }

        if ($this->igbinaryEmptyData === null) {
            // see igbinary/igbinary/issues/146
            $this->igbinaryEmptyData = \function_exists('igbinary_serialize') ? igbinary_serialize([]) : '';
        }

        if ($data === '' || $this->igbinaryEmptyData === $data) {
            return $this->destroy($id);
        }

        $this->newSessionId = null;

        return $this->doWrite($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $id): bool
    {
        if (\PHP_VERSION_ID < 70000) {
            $this->prefetchData = null;
        }

        return $this->newSessionId === $id || $this->doDestroy($id);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc(int $max_lifetime): int|false
    {
        // Not required here because cache will auto expire the records anyhow.
        return 0;
    }

    abstract protected function doRead(string $id): string;

    abstract protected function doWrite(string $sessionId, string $data): bool;

    abstract protected function doDestroy(string $sessionId): bool;
}
