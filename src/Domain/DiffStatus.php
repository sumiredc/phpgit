<?php

declare(strict_types=1);

namespace Phpgit\Domain;

enum DiffStatus: string
{
    case None = '';
    case Added = 'A';
    case Modified = 'M';
    case Deleted = 'D';
}
