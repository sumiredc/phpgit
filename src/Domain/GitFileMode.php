<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use UnexpectedValueException;

enum GitFileMode: string
{
    case Unknown = '0';
    case Tree = '40000';
    case DefaultFile = '100644';
    case ExeFile = '100755';
    case SymbolicLink = '120000';
    case SubModule = '160000';

    public function value6len(): string
    {
        return str_pad($this->value, 6, '0', STR_PAD_LEFT);
    }

    public function fileStatMode(): int
    {
        return match ($this) {
            self::DefaultFile, self::ExeFile => intval(octdec($this->value)),
            default => throw new UnexpectedValueException(sprintf('Unsupported GitFileMode: %s', $this->name)),
        };
    }

    public function toObjectType(): ObjectType
    {
        return match ($this) {
            self::DefaultFile, self::ExeFile => ObjectType::Blob,
            self::Tree => ObjectType::Tree,
            default => throw new UnexpectedValueException(sprintf("Unsupported GitFileMode: %s", $this->name)),
        };
    }
}
