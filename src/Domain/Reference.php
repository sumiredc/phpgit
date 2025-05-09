<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;

final class Reference
{
    public string $path {
        get => sprintf('%s/%s', $this->refType->prefix(), $this->name);
    }

    public string $fullPath {
        get => sprintf('%s/%s/%s', F_GIT_DIR, $this->refType->prefix(), $this->name);
    }

    private function __construct(
        public readonly ReferenceType $refType,
        public readonly string $name,
    ) {}

    public static function new(ReferenceType $refType, string $name): self
    {
        return new self($refType, $name);
    }

    /** @throws InvalidArgumentException */
    public static function parse(string $ref): self
    {
        $refType = match (0) {
            strpos($ref, ReferenceType::Local->prefix()) => ReferenceType::Local,
            strpos($ref, ReferenceType::Remote->prefix()) => ReferenceType::Remote,
            strpos($ref, ReferenceType::Tag->prefix()) => ReferenceType::Tag,
            strpos($ref, ReferenceType::Note->prefix()) => ReferenceType::Note,
            strpos($ref, ReferenceType::Stash->prefix()) => ReferenceType::Stash,
            strpos($ref, ReferenceType::Replace->prefix()) => ReferenceType::Replace,
            strpos($ref, ReferenceType::Bisect->prefix()) => ReferenceType::Bisect,
            default => throw new InvalidArgumentException(sprintf('failed to parse reference: %s', $ref))
        };

        // NOTE: does not use slash to delimiters on both ends so include "/" in prefix
        $pattern = sprintf('{^%s/(.+)}', quotemeta($refType->prefix()));
        $name = preg_replace($pattern, '$1', $ref);

        return new self($refType, $name);
    }

    public static function tryParse(string $ref): ?self
    {
        try {
            return self::parse($ref);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
