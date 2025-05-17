<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum UpdateRefOptionAction
{
    case Update;
    case Delete;
}
