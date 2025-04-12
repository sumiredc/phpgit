<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\TrackingFile;

interface FileRepositoryInterface
{
    public function exists(TrackingFile $trackingFile): bool;

    public function getContents(TrackingFile $trackingFile): ?string;

    public function getStat(TrackingFile $trackingFile): ?FileStat;
}
