<?php

declare(strict_types=1);

namespace Phpgit\Domain;

readonly final class IndexPaddingSize
{
    private function __construct(
        public readonly int $value
    ) {}

    public static function new(IndexEntrySize $indexEntrySize): self
    {
        return new self((8 - ($indexEntrySize->value % 8)) % 8);
    }

    public function isEmpty(): bool
    {
        return $this->value === 0;
    }

    public function asPadding(): string
    {
        return str_repeat("\0", $this->value);
    }
}
