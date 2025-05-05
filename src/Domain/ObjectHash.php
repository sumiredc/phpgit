<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;

readonly final class ObjectHash
{
    public readonly string $dir;
    public readonly string $filename;

    private function __construct(
        public readonly string $value,
    ) {
        $this->dir = substr($value, 0, 2);
        $this->filename = substr($value, 2);
    }

    public static function new(string $object): self
    {
        return new self(sha1($object));
    }

    /** @throws InvalidArgumentException */
    public static function parse(string $hash): self
    {
        if (preg_match('/^[0-9a-f]{40}$/i', $hash) !== 1) {
            throw new InvalidArgumentException(sprintf('invalid argument: %s', $hash));
        }

        return new self($hash);
    }

    public static function tryParse(string $hash): ?self
    {
        try {
            return self::parse($hash);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function path(): string
    {
        return implode('/', [$this->dir, $this->filename]);
    }

    public function fullPath(): string
    {
        return implode('/', [F_GIT_OBJECTS_DIR, $this->dir, $this->filename]);
    }
}
