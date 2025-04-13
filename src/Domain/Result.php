<?php

declare(strict_types=1);

namespace Phpgit\Domain;

enum Result: int
{
    case Success = 0;
    case Failure = 1;
    case Invalid = 2;

    case GitError = 128;
}
