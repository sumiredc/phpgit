<?php

declare(strict_types=1);

namespace Phpgit\Domain;

enum IndexObjectType: int
{
    case Normal = 0b1000;
    case SymbolicLink = 0b1010;
    case GitLink = 0b1110;

    /**
     * NOTE: 4bit
     */
    public function asStorableValue(): int
    {
        return $this->value << 12;
    }
}
