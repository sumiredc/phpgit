<?php

declare(strict_types=1);

namespace Phpgit\Helper;

use LogicException;
use Phpgit\Domain\BlobObject;
use Phpgit\Domain\DiffStat;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TreeEntry;
use SebastianBergmann\Diff\Differ;

readonly final class DiffIndexHelper implements DiffIndexHelperInterface
{
    public function __construct(
        private readonly FileRepositoryInterface $fileRepository,
        private readonly ObjectRepositoryInterface $objectRepository,
    ) {}

    /**
     * @param array<string,IndexEntry> $indexEntries
     * @param HashMap<TreeEntry> $treeEntries
     */
    public function targetEntry(array &$indexEntries, HashMap &$treeEntries): ?string
    {
        $a = $this->currentEntry($indexEntries);
        $b = $this->currentEntry($treeEntries);

        if (is_null($a) && is_null($b)) {
            return null;
        }

        if (is_null($a)) {
            return $b;
        }

        if (is_null($b)) {
            return $a;
        }

        $entries = [$a, $b];
        sort($entries, GIT_SORT);

        return $entries[0];
    }

    /**
     * @param HashMap<TreeEntry> $treeEntries
     * @param array<string,IndexEntry> $indexEntries
     */
    public function nextTargetEntry(
        ?TreeEntry $old,
        ?IndexEntry $new,
        HashMap &$treeEntries,
        array &$indexEntries
    ): ?string {
        if (!is_null($old)) {
            $treeEntries->next();
        }

        if (!is_null($new)) {
            next($indexEntries);
        }

        return $this->targetEntry($indexEntries, $treeEntries);
    }

    /**
     * @param iterable $entries
     */
    private function currentEntry(iterable &$entries): ?string
    {
        $entry = is_object($entries) ? $entries->key() : key($entries);
        if (is_null($entry)) {
            return null;
        }

        return strval($entry);
    }

    /**
     * @return array{0:GitFileMode,1:ObjectHash}
     */
    public function getOldStatusFromTree(?TreeEntry $entry): array
    {
        if (is_null($entry)) {
            // case: New file
            return [GitFileMode::Unknown, ObjectHash::zero()];
        }

        return [$entry->gitFileMode, $entry->objectHash];
    }

    /**
     * @return array{0:GitFileMode,1:ObjectHash}
     */
    public function getNewStatusFromIndex(?IndexEntry $entry): array
    {
        if (is_null($entry)) {
            // case: Removed from index
            return [GitFileMode::Unknown, ObjectHash::zero()];
        }

        return [$entry->gitFileMode, $entry->objectHash];
    }

    /**
     * @return array{0:GitFileMode,1:ObjectHash}
     */
    public function getNewStatusFromWorktree(?IndexEntry $entry): array
    {
        if (is_null($entry)) {
            // case: Removed from index
            return [GitFileMode::Unknown, ObjectHash::zero()];
        }

        if (!$this->fileRepository->exists($entry->trackedPath)) {
            // case: Deleted file
            return [GitFileMode::Unknown, ObjectHash::zero()];
        }

        $contents = $this->fileRepository->getContents($entry->trackedPath);
        $blob = BlobObject::new($contents);
        $hash = ObjectHash::new($blob->data);

        $stat = $this->fileRepository->getStat($entry->trackedPath);
        $newEntry = IndexEntry::new($stat, $hash, $entry->trackedPath);

        if (
            $newEntry->objectHash->value !== $entry->objectHash->value
            || $newEntry->gitFileMode !== $entry->gitFileMode
        ) {
            // case: Modified file
            return [$newEntry->gitFileMode, ObjectHash::zero()];
        }

        return [$entry->gitFileMode, $hash];
    }

    public function getOldContentsFromTree(?TreeEntry $entry): ?string
    {
        if (is_null($entry)) {
            return null;
        }

        $blob = $this->objectRepository->getBlob($entry->objectHash);

        return $blob->body;
    }

    public function getNewContentsFromIndex(?IndexEntry $entry): ?string
    {
        if (is_null($entry)) {
            return null;
        }

        if (!$this->objectRepository->exists($entry->objectHash)) {
            return null;
        }

        $blob = $this->objectRepository->getBlob($entry->objectHash);

        return $blob->body;
    }

    public function getNewContentsFromWorktree(?IndexEntry $entry): ?string
    {
        if (is_null($entry)) {
            return null;
        }

        if (!$this->fileRepository->exists($entry->trackedPath)) {
            return null;
        }

        return $this->fileRepository->getContents($entry->trackedPath);
    }

    public function countDiff(Differ $differ, ?string $old, ?string $new, string $path): DiffStat
    {
        $state = DiffStat::new($path);
        if (is_null($old) && is_null($new)) {
            return $state;
        }

        if (is_null($new)) {
            $state->dropedFile();

            return $state;
        }

        if (is_null($old)) {
            // NOTE: want to count the diff on new file
            $old = '';
            $state->addedFile();
        }

        $diff = $differ->diffToArray($old, $new);

        foreach ($diff as $line) {
            if (!isset($line[1])) {
                throw new LogicException('LIBRARY ERROR: undefined line index 1'); // @codeCoverageIgnore
            }

            switch ($line[1]) {
                case $differ::ADDED:
                    $state->insert();
                    break;
                case $differ::REMOVED:
                    $state->delete();
                    break;
            }
        }

        return $state;
    }

    public function isSame(
        GitFileMode $oldMode,
        GitFileMode $newMode,
        ObjectHash $oldHash,
        ObjectHash $newHash,
    ): bool {
        return $oldMode === $newMode && $oldHash->value === $newHash->value;
    }

    public function isAdded(GitFileMode $oldMode, ObjectHash $oldHash): bool
    {
        return $oldMode === GitFileMode::Unknown && $oldHash->isZero();
    }

    public function isModefied(GitFileMode $oldMode, ObjectHash $oldHash, GitFileMode $newMode, ObjectHash $newHash): bool
    {
        if (
            $oldHash->isZero()
            || $newMode === GitFileMode::Unknown
        ) {
            return false;
        }

        return $oldHash->value !== $newHash->value
            || $oldMode !== $newMode;
    }

    public function isDeleted(GitFileMode $newMode, ObjectHash $newHash): bool
    {
        return $newMode === GitFileMode::Unknown && $newHash->isZero();
    }
}
