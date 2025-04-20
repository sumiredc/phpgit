<?php

declare(strict_types=1);

namespace Phpgit\Domain;

enum IndexObjectType: int
{
    case Normal = 0b1000;
    case SymbolicLink = 0b1010;
    case GitLink = 0b1110;

    /**
     * NOTE: the upper 4bit in object flags
     */
    public static function parseFlags(int $flags): self
    {
        return self::from(($flags >> 12) & 0b1111);
    }

    /**
     * NOTE: 4bit
     */
    public function asStorableValue(): int
    {
        return $this->value << 12;
    }
}
