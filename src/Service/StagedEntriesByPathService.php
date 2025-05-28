<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\PathType;
use Phpgit\Domain\Service\PathInDirectory;
use Phpgit\Domain\Service\PathMatchPattern;
use Phpgit\Domain\TrackedPath;

final class StagedEntriesByPathService
{
    /**
     * @return HashMap<IndexEntry>
     */
    public function __invoke(GitIndex $gitIndex, TrackedPath $trackedPath, PathType $pathType): HashMap
    {
        $entries = HashMap::new();

        foreach ($gitIndex->entries as $path => $entry) {
            if ($this->isMatch($entry, $trackedPath, $pathType)) {
                $entries->set($path, $entry);
            }
        }

        return $entries;
    }

    private function isMatch(IndexEntry $entry, TrackedPath $path, PathType $type): bool
    {
        return match ($type) {
            PathType::File => $entry->trackedPath->value === $path->value,
            PathType::Directory => PathInDirectory::isUnder($entry->trackedPath->full(), $path->full()),
            PathType::Pattern => PathMatchPattern::matches($entry->trackedPath->value, $path->value),
            // NOTE: don't match path or deleted directory
            PathType::Unknown => PathInDirectory::isUnder($entry->trackedPath->full(), $path->full()),
        };
    }
}
