<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\ObjectHash;
use RuntimeException;

final class ObjectHashFactory
{
    public static function new(): ObjectHash
    {
        return ObjectHash::new('dummy object');
    }

    /** @throws RuntimeException */
    public static function random(): ObjectHash
    {
        // NOTE: 重複が発生する場合は遅延実行を検討する
        // time_nanosleep(0, 1);

        $ntime = hrtime(true);
        if ($ntime === false) {
            throw new RuntimeException('fails funciton by hrtime');
        }

        return ObjectHash::new(strval($ntime));
    }
}
