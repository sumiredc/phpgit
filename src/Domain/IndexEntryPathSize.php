<?php

declare(strict_types=1);

namespace Phpgit\Domain;

readonly final class IndexEntryPathSize
{
    private const UPPER_LIMIT_VALUE = 0xFFF;

    /**
     * NOTE: length of path with null-terminated string
     */
    public readonly int $withNull;

    private function __construct(
        public readonly int $value
    ) {
        $this->withNull = $value + 1;
    }

    public static function new(string $path): self
    {
        return new self(strlen($path));
    }

    /**
     * NOTE: Path length is lowest 12 bit in flags 
     */
    public static function parse(int $flags): self
    {
        return new self($flags & 0x0FFF);
    }

    public function isOverFlagsSpace(): bool
    {
        return $this->value > self::UPPER_LIMIT_VALUE;
    }

    public function asStorableValue(): int
    {
        return min(self::UPPER_LIMIT_VALUE, $this->value);
    }
}
