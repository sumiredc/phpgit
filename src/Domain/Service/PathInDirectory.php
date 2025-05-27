<?php

declare(strict_types=1);

namespace Phpgit\Domain\Service;

use InvalidArgumentException;

readonly final class PathInDirectory
{
    public const PATTERN = '/[*?\[\]{}]/';

    public static function isUnder(string $absolutePath, string $absoluteDir): bool
    {
        if (!str_starts_with($absolutePath, '/')) {
            throw new InvalidArgumentException(sprintf('Unfollow not absolute path: %s', $absolutePath));
        }

        if (!str_starts_with($absoluteDir, '/')) {
            throw new InvalidArgumentException(sprintf('Unfollow not absolute dir: %s', $absoluteDir));
        }

        $dirWithEndSlash = sprintf('%s%s', rtrim($absoluteDir, '/'), '/');

        return str_starts_with($absolutePath, $dirWithEndSlash);
    }
}
