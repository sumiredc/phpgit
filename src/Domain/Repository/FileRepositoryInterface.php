<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\TrackingFile;

interface FileRepositoryInterface
{
    public function exists(TrackingFile $trackingFile): bool;

    public function existsByFilename(string $file): bool;

    public function existsDir(TrackingFile $trackingFile): bool;

    public function existsDirByDirname(string $dir): bool;

    /**
     * @throws InvalidArgumentException
     */
    public function isOutSideRepository(string $path): bool;

    /** 
     * @throws RuntimeException 
     */
    public function getContents(TrackingFile $trackingFile): string;

    /** 
     * @throws RuntimeException 
     */
    public function getStat(TrackingFile $trackingFile): FileStat;

    /** 
     * @return array<TrackingFile>
     */
    public function search(string $path): array;
}
