<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum UpdateIndexOptionAction
{
    case Add;
    case Remove;
    case ForceRemove;
    case Cacheinfo;
}
