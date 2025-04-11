<?php

namespace Phpgit\Repository;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\TrackingFile;

final class FileRepository implements FileRepositoryInterface
{
    public function exists(TrackingFile $trackingFile): bool
    {
        return is_file($trackingFile->fullPath());
    }

    public function getContents(TrackingFile $trackingFile): ?string
    {
        $content = file_get_contents($trackingFile->fullPath());
        if ($content === false) {
            return null;
        }

        return $content;
    }

    public function getStat(TrackingFile $trackingFile): ?FileStat
    {
        $stat = stat($trackingFile->fullPath());
        if ($stat === false) {
            return null;
        }

        return FileStat::make($stat);
    }
}
