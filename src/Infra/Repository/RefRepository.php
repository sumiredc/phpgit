<?php

declare(strict_types=1);

namespace Phpgit\Infra\Repository;

use Phpgit\Domain\HeadType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Domain\Service\HashPattern;
use Phpgit\Domain\Service\RefPattern;
use RuntimeException;

readonly final class RefRepository implements RefRepositoryInterface
{
    public function exists(Reference $ref): bool
    {
        return is_file($ref->fullPath);
    }

    /** 
     * @throws RuntimeException
     */
    public function create(Reference $ref, ObjectHash $hash): void
    {
        if ($this->exists($ref)) {
            throw new RuntimeException(sprintf('Reference already exists: %s', $ref->path));
        }

        $this->createOrUpdate($ref, $hash);
    }

    /**
     * @throws RuntimeException
     */
    public function update(Reference $ref, ObjectHash $hash): void
    {
        if (!$this->exists($ref)) {
            throw new RuntimeException(sprintf('Reference not found: %s', $ref->path));
        }

        $this->createOrUpdate($ref, $hash);
    }

    /**
     * @throws RuntimeException
     */
    public function updateHead(ObjectHash $hash): void
    {
        $data = sprintf("%s\n", $hash->value);
        if (@file_put_contents(F_GIT_HEAD, $data) === false) {
            throw new RuntimeException(sprintf('failed to file_put_contents: %s', F_GIT_HEAD)); // @codeCoverageIgnore
        }
    }

    /**
     * @throws RuntimeException
     */
    public function createOrUpdate(Reference $ref, ObjectHash $hash): void
    {
        $data = sprintf("%s\n", $hash->value);
        if (@file_put_contents($ref->fullPath, $data) === false) {
            throw new RuntimeException(sprintf('failed to file_put_contents: %s', $ref->path)); // @codeCoverageIgnore
        }
    }

    /**
     * @throws RuntimeException
     */
    public function delete(Reference $ref): void
    {
        if (@unlink($ref->fullPath) === false) {
            throw new RuntimeException(sprintf('failed to delete file: %s', $ref->path)); // @codeCoverageIgnore
        }
    }

    public function headType(): HeadType
    {
        $fp = @fopen(F_GIT_HEAD, 'r');

        if ($fp === false) {
            return HeadType::Unknown;
        }

        try {
            $line = fgets($fp);
            if ($line === false) {
                return HeadType::Unknown;
            }

            if (HashPattern::sha1($line)) {
                return HeadType::Hash;
            }

            if (!is_null(RefPattern::parsePath($line))) {
                return HeadType::Reference;
            }
            return HeadType::Unknown;
        } finally {
            fclose($fp);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function head(): ?Reference
    {
        switch ($this->headType()) {
            case HeadType::Unknown:
                throw new RuntimeException('HEAD is Unknown');
            case HeadType::Hash:
                return null;
        }

        $fp = @fopen(F_GIT_HEAD, 'r');

        if ($fp === false) {
            throw new RuntimeException('failed to fopen by HEAD'); // @codeCoverageIgnore
        }

        try {
            $line = fgets($fp);
            if ($line === false) {
                throw new RuntimeException('failed to fgets by HEAD first line'); // @codeCoverageIgnore
            }

            $ref = RefPattern::parsePath($line);
            if (is_null($ref)) {
                throw new RuntimeException(sprintf('HEAD is not Reference: %s', $ref));  // @codeCoverageIgnore
            }

            return Reference::parse($ref);
        } finally {
            fclose($fp);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function resolve(Reference $ref): ObjectHash
    {
        $fp = @fopen($ref->fullPath, 'r');

        if ($fp === false) {
            throw new RuntimeException(sprintf('failed to fopen: %s', $ref->path));
        }

        try {
            $line = fgets($fp);
            if ($line === false) {
                throw new RuntimeException(sprintf('failed to fgets: %s', $ref->path));
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
    public function resolveHead(): ObjectHash
    {
        $fp = @fopen(F_GIT_HEAD, 'r');

        if ($fp === false) {
            throw new RuntimeException('failed to fopen by HEAD');
        }

        try {
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

    public function dereference(string $value): ?Reference
    {
        if ($value === GIT_HEAD) {
            return $this->head();
        }

        return Reference::tryParse($value);
    }
}
