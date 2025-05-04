<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\GitConfig;

final class GitConfigFactory
{
    public static function new(): GitConfig
    {
        return GitConfig::new(
            1,
            false,
            false,
            false,
            false,
            false,
            'Dummy User',
            'dummy@example.com'
        );
    }
}
