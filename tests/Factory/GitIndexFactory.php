<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\GitIndex;

final class GitIndexFactory
{
    public static function new(): GitIndex
    {
        return GitIndex::new();
    }
}
