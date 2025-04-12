<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum GitLsFileOptionAction
{
    case Default;
    case Stage;
    case Debug;
}
