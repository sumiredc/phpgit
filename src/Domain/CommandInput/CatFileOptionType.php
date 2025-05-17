<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum CatFileOptionType
{
    case Type;
    case Size;
    case Exists;
    case PrettyPrint;
}
