<?php

namespace Phpgit\Domain;


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

    public static function make(string $object): self
    {
        return new self(sha1($object));
    }

    public static function parse(string $value): ?self
    {
        if (preg_match('/^[0-9a-f]{40}$/i', $value) !== 1) {
            return null;
        }

        return new self($value);
    }

    public function value(): string
    {
        return sprintf('%s%s', $this->dir, $this->filename);
    }
}
