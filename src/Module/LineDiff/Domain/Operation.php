<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff\Domain;

enum Operation
{
    case Equal;
    case Insert;
    case Delete;
}
