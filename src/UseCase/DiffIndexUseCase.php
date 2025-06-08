<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use LogicException;
use Phpgit\Domain\BlobObject;
use Phpgit\Domain\CommandInput\DiffIndexOptionAction;
use Phpgit\Domain\DiffState;
use Phpgit\Domain\DiffStatus;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TreeEntry;
use Phpgit\Exception\UseCaseException;
use Phpgit\Request\DiffIndexRequest;
use Phpgit\Service\ResolveRevisionServiceInterface;
use Phpgit\Service\TreeToFlatEntriesServiceInterface;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Throwable;

final class DiffIndexUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly IndexRepositoryInterface $indexRepository,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly FileRepositoryInterface $fileRepository,
        private readonly ResolveRevisionServiceInterface $resolveRevisionService,
        private readonly TreeToFlatEntriesServiceInterface $treeToFlatEntriesService,
    ) {}

    public function __invoke(DiffIndexRequest $request): Result
    {
        try {
            $objectHash = ($this->resolveRevisionService)($request->treeIsh);
            throw_if(
                is_null($objectHash),
                new UseCaseException(
                    sprintf('fatal: ambiguous argument \'%s\': unknown revision or path not in the working tree.', $request->treeIsh)
                )
            );

            throw_unless(
                $this->objectRepository->exists($objectHash),
                new UseCaseException(sprintf('fatal: bad object %s', $objectHash->value))
            );

            $commitObject = $this->objectRepository->getCommit($objectHash);
            $treeObject = $this->objectRepository->getTree($commitObject->treeHash());

            $gitIndex = $this->indexRepository->getOrCreate();

            $treeEntries = ($this->treeToFlatEntriesService)($treeObject);
            $indexEntries = $gitIndex->entries;

            /**
             * NOTE: 
             *  isCached: Check diff between "specified tree" and "working tree"
             *  unCached: Check diff between "specified tree" and "staging area"
             */
            match ($request->action) {
                DiffIndexOptionAction::Default => $this->actionDefault($request->isCached, $indexEntries, $treeEntries),
                DiffIndexOptionAction::Stat => $this->actionStat($request->isCached, $indexEntries, $treeEntries),
            };

            return Result::Success;
        } catch (UseCaseException $ex) {
            $this->printer->writeln($ex->getMessage());

            return Result::GitError;
        } catch (Throwable $th) {
            $this->printer->stackTrace($th);

            return Result::InternalError;
        }
    }

    /**
     * @param array<string,IndexEntry> $indexEntries
     * @param HashMap<TreeEntry> $treeEntries
     */
    private function actionDefault(bool $isCached, array $indexEntries, HashMap $treeEntries): void
    {
        $target = $this->targetEntry($indexEntries, $treeEntries);

        while (!is_null($target)) {
            $old = $treeEntries->get($target);
            $new = $indexEntries[$target] ?? null;

            [$oldMode, $oldHash] = $this->getOldStatusFromTree($old);
            [$newMode, $newHash] = $isCached
                ? $this->getNewStatusFromIndex($new)
                : $this->getNewStatusFromWorktree($new);

            $this->printDiff($oldMode, $newMode, $oldHash, $newHash, $target);

            $target = $this->nextTargetEntry($old, $new, $treeEntries, $indexEntries);
        }
    }

    /**
     * @param array<string,IndexEntry> $indexEntries
     * @param HashMap<TreeEntry> $treeEntries
     */
    private function actionStat(bool $isCached, array $indexEntries, HashMap $treeEntries): void
    {
        $insertions = 0;
        $deletions = 0;
        $fileChanged = 0;
        $maxPathLen = 0;
        $maxDiffDigits = 1;
        $diffStates = [];

        $differ = new Differ(new UnifiedDiffOutputBuilder);
        $target = $this->targetEntry($indexEntries, $treeEntries);

        while (!is_null($target)) {
            $old = $treeEntries->get($target);
            $new = $indexEntries[$target] ?? null;

            $oldContents = $this->getOldContentsFromTree($old);
            $newContents = $isCached
                ? $this->getNewContentsFromIndex($new)
                : $this->getNewContentsFromWorktree($new);

            $diff = $this->countDiff($differ, $oldContents, $newContents, $target);

            if ($diff->isChanged()) {
                $insertions += $diff->insertions;
                $deletions += $diff->deletions;
                $fileChanged++;
                $maxPathLen = max($maxPathLen, strlen($target));
                $maxDiffDigits = max($maxDiffDigits, strlen(strval($diff->total)));
                $diffStates[] = $diff;
            }

            $target = $this->nextTargetEntry($old, $new, $treeEntries, $indexEntries);
        }

        foreach ($diffStates as $state) {
            $this->printer->writelnDiffStat(
                $maxPathLen,
                $maxDiffDigits,
                $state->path,
                $state->insertions,
                $state->deletions
            );
        }

        if ($fileChanged === 0) {
            return;
        }

        $this->printer->writeln(
            sprintf(
                ' %d files changed, %d insertions(+), %d deletions(-)',
                $fileChanged,
                $insertions,
                $deletions
            )
        );
    }

    private function targetEntry(array &$indexEntries, HashMap &$treeEntries): ?string
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

    private function nextTargetEntry(
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
        $entry =  is_object($entries) ? $entries->key() : key($entries);
        if (is_null($entry)) {
            return null;
        }

        return strval($entry);
    }

    /**
     * @return array{0:GitFileMode,1:ObjectHash}
     */
    private function getOldStatusFromTree(?TreeEntry $entry): array
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
    private function getNewStatusFromIndex(?IndexEntry $entry): array
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
    private function getNewStatusFromWorktree(?IndexEntry $entry): array
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

    private function getOldContentsFromTree(?TreeEntry $entry): ?string
    {
        if (is_null($entry)) {
            return null;
        }

        $blob = $this->objectRepository->getBlob($entry->objectHash);

        return $blob->body;
    }

    private function getNewContentsFromIndex(?IndexEntry $entry): ?string
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

    private function getNewContentsFromWorktree(?IndexEntry $entry): ?string
    {
        if (is_null($entry)) {
            return null;
        }

        if (!$this->fileRepository->exists($entry->trackedPath)) {
            return null;
        }

        return $this->fileRepository->getContents($entry->trackedPath);
    }

    private function countDiff(Differ $differ, ?string $old, ?string $new, string $path): DiffState
    {
        $state = DiffState::new($path);
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
                throw new LogicException('LIBRARY ERROR: undefined line index 1');
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

    /**
     * format
     *  :<old mode> <new mode> <old hash> <new hash> <status>\t<path>
     */
    private function printDiff(
        GitFileMode $oldMode,
        GitFileMode $newMode,
        ObjectHash $oldHash,
        ObjectHash $newHash,
        string $path
    ): void {
        $status = match (true) {
            $this->isSame($oldMode, $newMode, $oldHash, $newHash) => DiffStatus::None,
            $this->isAdded($oldMode, $oldHash) => DiffStatus::Added,
            $this->isModefied($oldMode, $oldHash, $newMode, $newHash) => DiffStatus::Modified,
            $this->isDeleted($newMode, $newHash) => DiffStatus::Deleted,
            default => throw new LogicException('Unable to determine file change status')
        };

        if ($status === DiffStatus::None) {
            return;
        }

        $this->printer->writeln(
            sprintf(
                ":%s %s %s %s %s\t%s",
                $oldMode->value6len(),
                $newMode->value6len(),
                $oldHash->value,
                $newHash->value,
                $status->value,
                $path
            )
        );
    }

    private function isSame(
        GitFileMode $oldMode,
        GitFileMode $newMode,
        ObjectHash $oldHash,
        ObjectHash $newHash,
    ): bool {
        return $oldMode === $newMode && $oldHash->value === $newHash->value;
    }

    private function isAdded(GitFileMode $oldMode, ObjectHash $oldHash): bool
    {
        return $oldMode === GitFileMode::Unknown && $oldHash->isZero();
    }

    private function isModefied(GitFileMode $oldMode, ObjectHash $oldHash, GitFileMode $newMode, ObjectHash $newHash): bool
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

    private function isDeleted(GitFileMode $newMode, ObjectHash $newHash): bool
    {
        return $newMode === GitFileMode::Unknown && $newHash->isZero();
    }
}
