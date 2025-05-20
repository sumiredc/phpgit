<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;

readonly final class TrackingFile
{
    private function __construct(
        public readonly string $path
    ) {}

    public static function new(string $path): self
    {
        return new self($path);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromFullPath(string $fullPath): self
    {
        if (strpos($fullPath, F_GIT_TRACKING_ROOT) !== 0) {
            throw new InvalidArgumentException(sprintf('Not match full path: %s', $fullPath));
        }

        $path = substr($fullPath, strlen(F_GIT_TRACKING_ROOT) + 1);

        return new self($path);
    }

    public function fullPath(): string
    {
        return sprintf('%s/%s', F_GIT_TRACKING_ROOT, $this->path);
    }
}
