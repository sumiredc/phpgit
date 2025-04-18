<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use ValueError;

enum GitFileMode: string
{
    case Tree = '040000';
    case DefaultFile = '100644';
    case ExeFile = '100755';
    case SymbolicLink = '120000';
    case SubModule = '160000';

    public function fileStatMode(): int
    {
        return match ($this) {
            self::DefaultFile, self::ExeFile => intval(octdec($this->value)),
            default => throw new ValueError(sprintf('don\'t convert to stat mode: %s', $this->value)),
        };
    }
}
