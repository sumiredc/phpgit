<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum AddOptionAction
{
    case Default;
    case All;
    case Update;
}
