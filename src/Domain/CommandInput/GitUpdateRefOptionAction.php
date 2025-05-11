<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum GitUpdateRefOptionAction
{
    case Update;
    case Delete;
}
