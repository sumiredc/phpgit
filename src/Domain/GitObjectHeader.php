<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;
use Phpgit\Domain\ObjectType;

final class GitObjectHeader
{
    public int $size {
        get => $this->size;
        set(int $value) {
            if ($value < 0) {
                throw new InvalidArgumentException(sprintf('invalid value: %d', $size));
            }
            $this->size = $value;
        }
    }

    public string $raw {
        get => sprintf("%s %d\0", $this->objectType->value, $this->size);
    }

    public static function new(ObjectType $type, int $size): self
    {
        return new self($type, $size);
    }

    private function __construct(public readonly ObjectType $objectType, int $size)
    {
        $this->size = $size;
    }
}
