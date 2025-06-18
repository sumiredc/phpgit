<?php

declare(strict_types=1);

namespace Phpgit\Domain;

enum ObjectType: string
{
    case Blob = 'blob';
    case Commit = 'commit';
    case Tree = 'tree';
}
