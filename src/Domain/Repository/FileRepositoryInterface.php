<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\TrackingPath;

interface FileRepositoryInterface
{
    public function exists(TrackingPath $trackingPath): bool;

    public function existsByFilename(string $file): bool;

    public function existsDir(TrackingPath $trackingPath): bool;

    public function existsDirByDirname(string $dir): bool;

    /**
     * @throws InvalidArgumentException
     */
    public function isOutSideRepository(string $path): bool;

    /** 
     * @throws RuntimeException 
     */
    public function getContents(TrackingPath $trackingPath): string;

    /** 
     * @throws RuntimeException 
     */
    public function getStat(TrackingPath $trackingPath): FileStat;

    /** 
     * @return array<TrackingPath>
     */
    public function search(string $path): array;
}
