<?php

declare(strict_types=1);

namespace Phpgit\Domain;

readonly final class GitSignature
{
    private function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly Timestamp $timestamp
    ) {}

    public static function new(
        string $name,
        string $email,
        Timestamp $timestamp
    ): self {
        return new self($name, $email, $timestamp);
    }

    public function toRawString(): string
    {
        return sprintf(
            '%s <%s> %s',
            $this->name,
            $this->email,
            $this->timestamp->toRawString()
        );
    }
}
