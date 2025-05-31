<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\PathType;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Service\PathPattern;
use Phpgit\Domain\TrackedPath;

final class GetPathTypeService implements GetPathTypeServiceInterface
{
    public function __construct(
        public readonly FileRepositoryInterface $fileRepository
    ) {}

    public function __invoke(GitIndex $gitIndex, TrackedPath $trackedPath): PathType
    {
        // TODO: シンボリックリンクは保留

        if ($this->fileRepository->existsDir($trackedPath)) {
            return PathType::Directory;
        }

        if (
            $this->fileRepository->exists($trackedPath)
            || $gitIndex->existsEntry($trackedPath)
        ) {
            return PathType::File;
        }

        if (PathPattern::is($trackedPath->value)) {
            return PathType::Pattern;
        }

        return PathType::Unknown;
    }
}
