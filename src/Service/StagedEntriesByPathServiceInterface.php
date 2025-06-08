<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\PathType;
use Phpgit\Domain\TrackedPath;

interface StagedEntriesByPathServiceInterface
{
    /**
     * @return HashMap<IndexEntry>
     */
    public function __invoke(GitIndex $gitIndex, TrackedPath $trackedPath, PathType $pathType): HashMap;
}
