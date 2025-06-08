<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum DiffIndexOptionAction
{
    case Default;
    case Cached;
    case Stat;
    case FindRenames;
}
