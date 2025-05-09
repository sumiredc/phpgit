<?php

declare(strict_types=1);

namespace Phpgit\Domain\Service;

readonly final class RefPattern
{
    private const REF_PATH = '/^ref: (.+)$/';

    public static function parsePath(string $value): ?string
    {
        if (preg_match(self::REF_PATH, $value, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
