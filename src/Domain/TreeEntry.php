<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;

readonly final class TreeEntry
{
    private function __construct(
        public readonly ObjectType $objectType,
        public readonly GitFileMode $gitFileMode,
        public readonly string $objectName,
        public readonly ObjectHash $objectHash
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public static function new(
        ObjectType $objectType,
        GitFileMode $gitFileMode,
        string $objectName,
        ObjectHash $objectHash
    ): self {
        if (in_array($objectType, [ObjectType::Blob, ObjectType::Tree], true)) {
            return new self($objectType, $gitFileMode, $objectName, $objectHash);
        }

        throw new InvalidArgumentException(sprintf('not allowed ObjectType: %s', $objectType->name));
    }
}
