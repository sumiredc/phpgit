<?php

declare(strict_types=1);

namespace Phpgit\Domain\Service;

readonly final class HashPattern
{
    private const SHA1 = '/^[0-9a-f]{40}$/';

    public static function sha1(string $value): bool
    {
        return preg_match(self::SHA1, $value, $matches) === 1;
    }
}
