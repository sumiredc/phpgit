<?php

declare(strict_types=1);

namespace Phpgit\Domain;

enum PathType
{
    case File;
    case Directory;
    case Pattern;
    case Unknown;
}
