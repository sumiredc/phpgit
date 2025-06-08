<?php

declare(strict_types=1);

namespace Phpgit\Helper;

use Phpgit\Domain\DiffState;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\TreeEntry;
use SebastianBergmann\Diff\Differ;

interface DiffIndexHelperInterface
{
    public function targetEntry(array &$indexEntries, HashMap &$treeEntries): ?string;

    public function nextTargetEntry(?TreeEntry $old, ?IndexEntry $new, HashMap &$treeEntries, array &$indexEntries): ?string;

    /**
     * @return array{0:GitFileMode,1:ObjectHash}
     */
    public function getOldStatusFromTree(?TreeEntry $entry): array;

    /**
     * @return array{0:GitFileMode,1:ObjectHash}
     */
    public function getNewStatusFromIndex(?IndexEntry $entry): array;

    /**
     * @return array{0:GitFileMode,1:ObjectHash}
     */
    public function getNewStatusFromWorktree(?IndexEntry $entry): array;

    public function getOldContentsFromTree(?TreeEntry $entry): ?string;

    public function getNewContentsFromIndex(?IndexEntry $entry): ?string;

    public function getNewContentsFromWorktree(?IndexEntry $entry): ?string;

    public function countDiff(Differ $differ, ?string $old, ?string $new, string $path): DiffState;

    public function isSame(GitFileMode $oldMode, GitFileMode $newMode, ObjectHash $oldHash, ObjectHash $newHash,): bool;

    public function isAdded(GitFileMode $oldMode, ObjectHash $oldHash): bool;

    public function isModefied(GitFileMode $oldMode, ObjectHash $oldHash, GitFileMode $newMode, ObjectHash $newHash): bool;

    public function isDeleted(GitFileMode $newMode, ObjectHash $newHash): bool;
}
