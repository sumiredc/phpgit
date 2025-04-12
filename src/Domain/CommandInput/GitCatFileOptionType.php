<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum GitCatFileOptionType
{
    case Type;
    case Size;
    case Exists;
    case PrettyPrint;
}
