<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\TrackingFile;

interface FileRepositoryInterface
{
    public function exists(TrackingFile $trackingFile): bool;

    public function existsByFilename(string $file): bool;

    /** @throws RuntimeException */
    public function getContents(TrackingFile $trackingFile): string;

    /** @throws RuntimeException */
    public function getStat(TrackingFile $trackingFile): FileStat;
}
