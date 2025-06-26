<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use LogicException;
use Phpgit\Domain\CommitObject;
use Phpgit\Domain\DiffStatus;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\HeadType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TreeObject;
use Phpgit\Exception\UseCaseException;
use Phpgit\Helper\DiffIndexHelperInterface;
use Phpgit\Request\CommitRequest;
use Phpgit\Service\CreateCommitTreeServiceInterface;
use Phpgit\Service\CreateSegmentTreeServiceInterface;
use Phpgit\Service\ResolveRevisionServiceInterface;
use Phpgit\Service\SaveTreeObjectServiceInterface;
use Phpgit\Service\TreeToFlatEntriesServiceInterface;
use Throwable;

readonly final class CommitUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly IndexRepositoryInterface $indexRepository,
        private readonly RefRepositoryInterface $refRepository,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly ResolveRevisionServiceInterface $resolveRevisionService,
        private readonly TreeToFlatEntriesServiceInterface $treeToFlatEntriesService,
        private readonly CreateSegmentTreeServiceInterface $createSegmentTreeService,
        private readonly SaveTreeObjectServiceInterface $saveTreeObjectService,
        private readonly CreateCommitTreeServiceInterface $createCommitTreeService,
        private readonly DiffIndexHelperInterface $diffIndexHelper
    ) {}

    public function __invoke(CommitRequest $request): Result
    {
        try {
            // rev-parse HEAD
            [$parentCommitHash, $parentCommit] = $this->getParentCommitHash();

            // diff-index --cached HEAD
            $gitIndex = $this->indexRepository->getOrCreate();
            $headType = $this->refRepository->headType();
            $currentHeadLabel = $this->currentHeadLabel($headType);
            $treeObject = is_null($parentCommit) ? null : $this->objectRepository->getTree($parentCommit->treeHash());
            [$fileChanged, $summary, $histories] = $this->diffIndex($gitIndex, $treeObject);

            throw_if(
                $fileChanged === 0,
                new UseCaseException(sprintf("%s\nnothing to commit, working tree clean", $currentHeadLabel))
            );

            // write-tree
            $segmentTree = ($this->createSegmentTreeService)($gitIndex);
            $treeHash = ($this->saveTreeObjectService)($segmentTree);

            // commit-tree -m <message> -p <parent>
            $commit = ($this->createCommitTreeService)($treeHash, $request->message, $parentCommitHash);
            $commitHash = $this->objectRepository->save($commit);

            // update-ref HEAD
            $ref = $this->updateRef($commitHash, $headType);

            $commitLabel = $this->createCommitLabel($headType, $commitHash, $ref, $parentCommit);

            $this->printer->writeln(sprintf('[%s] %s', $commitLabel, $request->message));
            $this->printer->writeln($summary);
            $this->printer->writeln($histories);

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
     * @return array{0:ObjectHash|null,1:CommitObject|null}
     */
    private function getParentCommitHash(): array
    {
        $commitHash = ($this->resolveRevisionService)(GIT_HEAD);
        if (is_null($commitHash)) {
            return [null, null];
        }

        return [
            $commitHash,
            $this->objectRepository->getCommit($commitHash)
        ];
    }

    /**
     * @return array{0:int,1:string,2:array<string>} fileChanged, summary, histories
     */
    private function diffIndex(GitIndex $gitIndex, ?TreeObject $treeObject): array
    {
        $insertions = 0;
        $deletions = 0;
        $fileChanged = 0;
        $histories = [];

        $treeEntries = is_null($treeObject)
            ? HashMap::new()
            : ($this->treeToFlatEntriesService)($treeObject);
        $indexEntries = $gitIndex->entries;

        $target = $this->diffIndexHelper->targetEntry($indexEntries, $treeEntries);

        while (!is_null($target)) {
            $old = $treeEntries->get($target);
            $new = $indexEntries[$target] ?? null;

            [$oldMode, $oldHash] = $this->diffIndexHelper->getOldStatusFromTree($old);
            [$newMode, $newHash] = $this->diffIndexHelper->getNewStatusFromIndex($new);

            $oldContents = $this->diffIndexHelper->getOldContentsFromTree($old);
            $newContents = $this->diffIndexHelper->getNewContentsFromIndex($new);

            $diff = $this->diffIndexHelper->countDiff($oldContents, $newContents, $target);

            if ($diff->isChanged()) {
                $insertions += $diff->insertions;
                $deletions += $diff->deletions;
                $fileChanged++;
            }

            $history = $this->diffSummaryLine($oldMode, $oldHash, $newMode, $newHash, $target);
            if (!is_null($history)) {
                $histories[] = $history;
            }

            $target = $this->diffIndexHelper->nextTargetEntry($old, $new, $treeEntries, $indexEntries);
        }

        return [
            $fileChanged,
            sprintf(' %d files changed, %d insertions(+), %d deletions(-)', $fileChanged, $insertions, $deletions),
            $histories
        ];
    }

    private function diffSummaryLine(
        GitFileMode $oldMode,
        ObjectHash $oldHash,
        GitFileMode $newMode,
        ObjectHash $newHash,
        string $path
    ): ?string {
        $status = match (true) {
            $this->diffIndexHelper->isSame($oldMode, $newMode, $oldHash, $newHash) => DiffStatus::None,
            $this->diffIndexHelper->isAdded($oldMode, $oldHash) => DiffStatus::Added,
            $this->diffIndexHelper->isModefied($oldMode, $oldHash, $newMode, $newHash) => DiffStatus::Modified,
            $this->diffIndexHelper->isDeleted($newMode, $newHash) => DiffStatus::Deleted,
            default => throw new LogicException('Unable to determine file change status') // @codeCoverageIgnore
        };

        return match ($status) {
            DiffStatus::Added => sprintf(" create mode %s %s", $newMode->value, $path),
            DiffStatus::Modified => $oldMode !== $newMode
                ? sprintf(" mode change %s => %s %s", $oldMode->value, $newMode->value, $path)
                : null,
            DiffStatus::Deleted => sprintf(" delete mode %s %s", $oldMode->value, $path),
            default => null
        };
    }

    private function updateRef(ObjectHash $commitHash, HeadType $headType): ?Reference
    {
        switch ($headType) {
            case HeadType::Hash:
                $this->refRepository->updateHead($commitHash);

                return null;

            case HeadType::Reference:
                $ref = $this->refRepository->head();
                throw_if(is_null($ref), new LogicException('HEAD is not reference'));

                $this->refRepository->createOrUpdate($ref, $commitHash);

                return $ref;

            default:
                throw new LogicException(sprintf('This branch is not reached: %s', $headType->name)); // @codeCoverageIgnore
        }
    }

    private function currentHeadLabel(HeadType $headType): string
    {
        switch ($headType) {
            case HeadType::Hash:
                $hash = $this->refRepository->resolveHead();

                return sprintf('HEAD detached at %s', $hash->short());

            case HeadType::Reference:
                $ref = $this->refRepository->head();
                throw_if(is_null($ref), new LogicException('This branch is not reached'));

                return sprintf('On branch %s', $ref->name);
        }

        throw new LogicException('This branch is not reached');
    }

    private function createCommitLabel(
        HeadType $headType,
        ObjectHash $commitHash,
        ?Reference $ref,
        ?CommitObject $parentCommit
    ): string {
        return match ($headType) {
            HeadType::Hash => sprintf('detached HEAD %s', $commitHash->short()),
            HeadType::Reference => is_null($parentCommit)
                ? sprintf('%s (root-commit) %s', $ref->name, $commitHash->short())
                : sprintf('%s %s', $ref->name, $commitHash->short())
        };
    }
}
