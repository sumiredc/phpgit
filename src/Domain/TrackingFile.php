<?php

declare(strict_types=1);

namespace Phpgit\Domain;

readonly final class TrackingFile
{
    public readonly string $fullPath;

    private function __construct(
        public readonly string $path
    ) {}

    public static function parse(string $path): self
    {
        return new self($path);
    }

    public function fullPath(): string
    {
        return sprintf('%s/%s', F_GIT_TRACKING_ROOT, $this->path);
    }
}
