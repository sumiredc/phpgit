<?php

declare(strict_types=1);

namespace Phpgit\Domain\Service;

readonly final class PathPattern
{
    public const PATTERN = '/[*?\[\]{}]/';

    public static function is(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }
}
