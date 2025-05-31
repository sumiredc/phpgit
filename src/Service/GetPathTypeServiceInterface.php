<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\PathType;
use Phpgit\Domain\TrackedPath;

interface GetPathTypeServiceInterface
{
    public function __invoke(GitIndex $gitIndex, TrackedPath $trackedPath): PathType;
}
