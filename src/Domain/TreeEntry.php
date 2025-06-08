<?php

declare(strict_types=1);

namespace Phpgit\Domain;

readonly final class TreeEntry
{
    private function __construct(
        public readonly ObjectType $objectType,
        public readonly GitFileMode $gitFileMode,
        public readonly string $objectName,
        public readonly ObjectHash $objectHash
    ) {}

    public static function new(
        ObjectType $objectType,
        GitFileMode $gitFileMode,
        string $objectName,
        ObjectHash $objectHash
    ): self {
        return new self($objectType, $gitFileMode, $objectName, $objectHash);
    }
}
