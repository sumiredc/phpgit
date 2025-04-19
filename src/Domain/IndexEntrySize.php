<?php

declare(strict_types=1);

namespace Phpgit\Domain;

readonly final class IndexEntrySize
{
    private function __construct(
        public readonly int $value
    ) {}

    public static function new(IndexEntryPathSize $pathSize): self
    {
        return new self(
            GIT_INDEX_ENTRY_HEADER_LENGTH
                + $pathSize->value
                + 1 // null-terminated string
        );
    }
}
