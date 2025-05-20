<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;

readonly final class TrackedPath
{
    private function __construct(
        public readonly string $value
    ) {}

    public static function parse(string $path): self
    {
        $fullPath = self::resolve($path);
        if (!str_starts_with($fullPath, sprintf('%s/', F_GIT_TRACKING_ROOT))) {
            throw new InvalidArgumentException(
                sprintf('The specified path "%s" is outside of the repository', $path)
            );
        }

        $relativePath = substr($fullPath, strlen(F_GIT_TRACKING_ROOT) + 1);

        return new self($relativePath);
    }

    private static function resolve(string $value): string
    {
        $path = $value;
        if (str_starts_with($path, '~')) {
            $path = preg_replace('/^~/', ROOT_DIR, $path);
        }

        if (!str_starts_with($path, '/')) {
            $path = sprintf('%s/%s', F_GIT_TRACKING_ROOT, $path);
        }

        $segments = [];
        $end = str_ends_with($path, '/') ? '/' : '';

        foreach (explode('/', $path) as $segment) {
            if (in_array($segment, ['', '.'], true)) {
                continue;
            }

            if ($segment === '..') {
                if (empty($segments)) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid path traversal detected: "%s" escapes the repository root', $value)

                    );
                }

                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return sprintf('/%s%s', implode('/', $segments), $end); // fullpath
    }

    public function full(): string
    {
        return sprintf('%s/%s', F_GIT_TRACKING_ROOT, $this->value);
    }
}
