<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use LogicException;
use Phpgit\Domain\BlobObject;
use Phpgit\Domain\CommandInput\DiffIndexOptionAction;
use Phpgit\Domain\DiffStatus;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TreeEntry;
use Phpgit\Domain\TreeObject;
use Phpgit\Exception\UseCaseException;
use Phpgit\Request\DiffIndexRequest;
use Phpgit\Service\ResolveRevisionServiceInterface;
use Phpgit\Service\TreeToFlatEntriesServiceInterface;
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

            match ($request->action) {
                DiffIndexOptionAction::Default => $this->actionDefault($gitIndex, $treeObject),
                DiffIndexOptionAction::Cached => $this->actionCached(),
                DiffIndexOptionAction::Stat => $this->actionStat(),
                DiffIndexOptionAction::FindRenames => $this->actionFindRenames(),
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
     * Check diff between "specified tree" and "working tree"
     */
    private function actionDefault(GitIndex $index, TreeObject $treeObject): void
    {
        $treeEntries = ($this->treeToFlatEntriesService)($treeObject);
        $indexEntries = $index->entries;

        $target = $this->targetEntry($indexEntries, $treeEntries);

        while (!is_null($target)) {
            $old = $treeEntries->get($target);
            $new = $indexEntries[$target] ?? null;

            [$oldMode, $oldHash] = $this->getOldStatusFromTree($old);
            [$newMode, $newHash] = $this->getNewStatusFromWorktree($new);

            $this->printDiff($oldMode, $newMode, $oldHash, $newHash, $target);

            $treeEntries->next();
            next($indexEntries);

            $target = $this->targetEntry($indexEntries, $treeEntries);
        }
    }

    /**
     * 
     */
    private function actionCached(): void {}

    /**
     * 
     */
    private function actionStat(): void {}

    /**
     * 
     */
    private function actionFindRenames(): void {}


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
            $this->isModefied($newMode, $newHash) => DiffStatus::Modified,
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

    private function isModefied(GitFileMode $newMode, ObjectHash $newHash): bool
    {
        return $newMode !== GitFileMode::Unknown && $newHash->isZero();
    }

    private function isDeleted(GitFileMode $newMode, ObjectHash $newHash): bool
    {
        return $newMode === GitFileMode::Unknown && $newHash->isZero();
    }
}
