<?php

declare(strict_types=1);

namespace Phpgit\Infra\Repository;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\TrackingFile;
use RuntimeException;

readonly final class FileRepository implements FileRepositoryInterface
{
    public function exists(TrackingFile $trackingFile): bool
    {
        return is_file($trackingFile->fullPath());
    }

    public function existsByFilename(string $file): bool
    {
        return is_file(sprintf('%s/%s', F_GIT_TRACKING_ROOT, $file));
    }

    /** @throws RuntimeException */
    public function getContents(TrackingFile $trackingFile): string
    {
        $content = file_get_contents($trackingFile->fullPath());
        if ($content === false) {
            throw new RuntimeException(sprintf('failed to get contents: %s', $trackingFile->fullPath()));
        }

        return $content;
    }

    /** @throws RuntimeException */
    public function getStat(TrackingFile $trackingFile): FileStat
    {
        $stat = stat($trackingFile->fullPath());
        if ($stat === false) {
            throw new RuntimeException(sprintf('failed to get stat: %s', $trackingFile->fullPath()));
        }

        return FileStat::new($stat);
    }
}
