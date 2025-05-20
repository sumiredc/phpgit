<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\TrackedPath;

interface FileRepositoryInterface
{
    public function exists(TrackedPath $trackedPath): bool;

    public function existsDir(TrackedPath $trackedPath): bool;

    public function existsDirByDirname(string $dir): bool;

    /** 
     * @throws RuntimeException 
     */
    public function getContents(TrackedPath $trackedPath): string;

    /** 
     * @throws RuntimeException 
     */
    public function getStat(TrackedPath $trackedPath): FileStat;

    /** 
     * @return array<TrackedPath>
     */
    public function search(string $path): array;
}
