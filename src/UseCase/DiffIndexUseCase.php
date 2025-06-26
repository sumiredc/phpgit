<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use LogicException;
use Phpgit\Domain\CommandInput\DiffIndexOptionAction;
use Phpgit\Domain\DiffStatus;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TreeEntry;
use Phpgit\Exception\UseCaseException;
use Phpgit\Helper\DiffIndexHelperInterface;
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
        private readonly ResolveRevisionServiceInterface $resolveRevisionService,
        private readonly TreeToFlatEntriesServiceInterface $treeToFlatEntriesService,
        private readonly DiffIndexHelperInterface $diffIndexHelper
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
        $target = $this->diffIndexHelper->targetEntry($indexEntries, $treeEntries);

        while (!is_null($target)) {
            $old = $treeEntries->get($target);
            $new = $indexEntries[$target] ?? null;

            [$oldMode, $oldHash] = $this->diffIndexHelper->getOldStatusFromTree($old);
            [$newMode, $newHash] = $isCached
                ? $this->diffIndexHelper->getNewStatusFromIndex($new)
                : $this->diffIndexHelper->getNewStatusFromWorktree($new);

            $this->printDiff($oldMode, $newMode, $oldHash, $newHash, $target);

            $target = $this->diffIndexHelper->nextTargetEntry($old, $new, $treeEntries, $indexEntries);
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

        $target = $this->diffIndexHelper->targetEntry($indexEntries, $treeEntries);

        while (!is_null($target)) {
            $old = $treeEntries->get($target);
            $new = $indexEntries[$target] ?? null;

            $oldContents = $this->diffIndexHelper->getOldContentsFromTree($old);
            $newContents = $isCached
                ? $this->diffIndexHelper->getNewContentsFromIndex($new)
                : $this->diffIndexHelper->getNewContentsFromWorktree($new);

            $diff = $this->diffIndexHelper->countDiff($oldContents, $newContents, $target);

            if ($diff->isChanged()) {
                $insertions += $diff->insertions;
                $deletions += $diff->deletions;
                $fileChanged++;
                $maxPathLen = max($maxPathLen, strlen($target));
                $maxDiffDigits = max($maxDiffDigits, strlen(strval($diff->total)));
                $diffStates[] = $diff;
            }

            $target = $this->diffIndexHelper->nextTargetEntry($old, $new, $treeEntries, $indexEntries);
        }

        foreach ($diffStates as $stat) {
            $this->printer->writelnDiffStat(
                $maxPathLen,
                $maxDiffDigits,
                $stat->path,
                $stat->insertions,
                $stat->deletions
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
            $this->diffIndexHelper->isSame($oldMode, $newMode, $oldHash, $newHash) => DiffStatus::None,
            $this->diffIndexHelper->isAdded($oldMode, $oldHash) => DiffStatus::Added,
            $this->diffIndexHelper->isModefied($oldMode, $oldHash, $newMode, $newHash) => DiffStatus::Modified,
            $this->diffIndexHelper->isDeleted($newMode, $newHash) => DiffStatus::Deleted,
            default => throw new LogicException('Unable to determine file change status'), // @codeCoverageIgnore
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
}
