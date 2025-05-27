<?php

declare(strict_types=1);

namespace Phpgit\Domain\Service;

readonly final class PathMatchPattern
{
    public static function matches(string $path, string $pattern): bool
    {
        return fnmatch($pattern, $path, FNM_PATHNAME);
    }
}
