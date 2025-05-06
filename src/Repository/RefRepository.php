<?php

declare(strict_types=1);

namespace Phpgit\Repository;

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Exception\FileAlreadyExistsException;
use Phpgit\Exception\FileNotFoundException;
use RuntimeException;

readonly final class RefRepository implements RefRepositoryInterface
{
    public function exists(Reference $ref): bool
    {
        return is_file($ref->fullPath);
    }

    /** 
     * @throws FileAlreadyExistsException
     * @throws RuntimeException
     */
    public function create(Reference $ref, ObjectHash $hash): void
    {
        if ($this->exists($ref)) {
            throw new FileAlreadyExistsException();
        }

        $data = sprintf("%s\n", $hash->value);
        if (file_put_contents($ref->fullPath, $data) === false) {
            throw new RuntimeException(sprintf('failed to create file: %s', $ref->fullPath));
        }
    }

    /**
     * @throws FileNotFoundException
     * @throws RuntimeException
     */
    public function update(Reference $ref, ObjectHash $hash): void
    {
        if (!$this->exists($ref)) {
            throw new FileNotFoundException();
        }

        $data = sprintf("%s\n", $hash->value);
        if (file_put_contents($ref->fullPath, $data) === false) {
            throw new RuntimeException(sprintf('failed to update file: %s', $ref->fullPath));
        }
    }

    /**
     * @throws RuntimeException
     */
    public function head(): ?Reference
    {
        $fp = fopen(F_GIT_HEAD, 'r');
        if ($fp === false) {
            throw new RuntimeException('failed to fopen by HEAD');
        }

        $line = fgets($fp);
        if ($line === false) {
            throw new RuntimeException('failed to fgets by HEAD first line');
        }

        if (!preg_match('/^ref: (.+)/', $line, $matches)) {
            // NOTE: The hash written directly
            return null;
        }

        return Reference::parse($matches[1]);
    }

    /**
     * @throws RuntimeException
     */
    public function resolve(Reference $ref): ObjectHash
    {
        $fp = fopen($ref->fullPath, 'r');
        if ($fp === false) {
            throw new RuntimeException(sprintf('failed to fopen: %s', $ref->fullPath));
        }

        $hash = fgets($fp);
        if ($hash === false) {
            throw new RuntimeException(sprintf('failed to fgets: %s', $ref->fullPath));
        }

        return ObjectHash::parse($hash);
    }
}
