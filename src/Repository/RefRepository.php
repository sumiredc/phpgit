<?php

declare(strict_types=1);

namespace Phpgit\Repository;

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Domain\Service\RefPattern;
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

    // /**
    //  * @throws RuntimeException
    //  */
    // public function head(): ?Reference
    // {
    //     $fp = @fopen(F_GIT_HEAD, 'r');

    //     try {
    //         if ($fp === false) {
    //             throw new RuntimeException('failed to fopen by HEAD');
    //         }

    //         $line = fgets($fp);
    //         if ($line === false) {
    //             throw new RuntimeException('failed to fgets by HEAD first line');
    //         }

    //         $ref = RefPattern::parsePath($line);
    //         if (is_null($ref)) {
    //             // NOTE: The hash written directly
    //             return null;
    //         }

    //         return Reference::parse($ref);
    //     } finally {
    //         fclose($fp);
    //     }
    // }

    /**
     * @throws RuntimeException
     */
    public function resolve(Reference $ref): ObjectHash
    {
        $fp = @fopen($ref->fullPath, 'r');

        try {
            if ($fp === false) {
                throw new RuntimeException(sprintf('failed to fopen: %s', $ref->fullPath));
            }

            $line = fgets($fp);
            if ($line === false) {
                throw new RuntimeException(sprintf('failed to fgets: %s', $ref->fullPath));
            }

            $hash = preg_replace('/\r\n|\n|\r/', '', $line);
            return ObjectHash::parse($hash);
        } finally {
            fclose($fp);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function resolveHead(): ?ObjectHash
    {
        $fp = @fopen(F_GIT_HEAD, 'r');

        try {
            if ($fp === false) {
                throw new RuntimeException('failed to fopen by HEAD');
            }

            $line = fgets($fp);
            if ($line === false) {
                throw new RuntimeException('failed to fgets by HEAD first line');
            }

            $path = RefPattern::parsePath($line);
            if (is_null($path)) {
                // NOTE: The hash written directly
                $hash = preg_replace('/\r\n|\n|\r/', '', $line);

                return ObjectHash::parse($hash);
            }

            $ref = Reference::parse($path);

            return $this->resolve($ref);
        } finally {
            fclose($fp);
        }
    }
}
