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
        if (strpos($fullPath, sprintf('%s/', F_GIT_TRACKING_ROOT)) !== 0) {
            throw new InvalidArgumentException(
                sprintf('The specified path "%s" is outside of the repository', $path)
            );
        }

        $relativePath = substr($fullPath, strlen(F_GIT_TRACKING_ROOT) + 1);

        return new self($relativePath);
    }

    private static function resolve(string $value): string
    {
        if (strpos($value, '~') === 0) {
            $value = preg_replace('/^~/', ROOT_DIR, $value);
        }

        if (strpos($value, '/') !== 0) {
            $value = sprintf('%s/%s', F_GIT_TRACKING_ROOT, $value);
        }

        $segments = [];

        foreach (explode('/', $value) as $segment) {
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

        return sprintf('/%s', implode('/', $segments)); // fullpath
    }

    public function full(): string
    {
        return sprintf('%s/%s', F_GIT_TRACKING_ROOT, $this->value);
    }
}
