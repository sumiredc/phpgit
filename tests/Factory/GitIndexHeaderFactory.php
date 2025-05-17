<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\GitIndexHeader;
use UnderflowException;

final class GitIndexHeaderFactory
{
    public static function new(int $count = 0): GitIndexHeader
    {
        if ($count < 0) {
            throw new UnderflowException;
        };

        $blob = pack(
            'a4NN',
            GIT_INDEX_SIGNATURE,
            GIT_INDEX_VERSION,
            $count,
        );

        return GitIndexHeader::parse($blob);
    }
}
