<?php

declare(strict_types=1);

namespace Phpgit\Domain;

enum Result
{
    case Success;
    case Invalid;
    case Failure;
}
