<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum GitUpdateIndexOptionAction
{
    case Add;
    case Remove;
    case ForceRemove;
    case Cacheinfo;
}
