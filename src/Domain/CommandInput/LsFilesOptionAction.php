<?php

declare(strict_types=1);

namespace Phpgit\Domain\CommandInput;

enum LsFilesOptionAction
{
    case Default;
    case Tag;
    case Zero;
    case Stage;
    case Debug;
}
