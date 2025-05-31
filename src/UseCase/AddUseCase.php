<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\CommandInput\AddOptionAction;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\PathType;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TrackedPath;
use Phpgit\Exception\UseCaseException;
use Phpgit\Request\AddRequest;
use Phpgit\Service\GetPathTypeServiceInterface;
use Phpgit\Service\StagedEntriesByPathServiceInterface;
use Throwable;
use UnhandledMatchError;

final class AddUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly FileRepositoryInterface $fileRepository,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly IndexRepositoryInterface $indexRepository,
        private readonly GetPathTypeServiceInterface $getPathTypeService,
        private readonly StagedEntriesByPathServiceInterface $stagedEntriesByPathService,
    ) {}

    public function __invoke(AddRequest $request): Result
    {
        $path = match ($request->action) {
            AddOptionAction::Default => $request->path,
            AddOptionAction::All => F_GIT_TRACKING_ROOT,
            AddOptionAction::Update => $request->path ?: F_GIT_TRACKING_ROOT,
            default => throw new UnhandledMatchError(sprintf('Unhandled enum case: %s', $request->action->name)), // @codeCoverageIgnore
        };

        try {
            $trackedPath = try_or_throw(
                fn() => TrackedPath::parse($path),
                UseCaseException::class,
                sprintf(
                    'fatal: %s: \'%s\' is outside repository at \'%s\'',
                    $request->path,
                    $request->path,
                    F_GIT_TRACKING_ROOT
                )
            );

            $gitIndex = $this->indexRepository->getOrCreate();
            $pathType = ($this->getPathTypeService)($gitIndex, $trackedPath);
            $targets = $this->fileRepository->search($trackedPath, $pathType);

            $gitIndex = $this->stageFilesToIndex(
                $gitIndex,
                $trackedPath,
                $pathType,
                $targets,
                $request->action
            );

            $this->indexRepository->save($gitIndex);

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
     * @param HashMap<TrackedPath> $targets
     * @throws UseCaseException
     */
    private function stageFilesToIndex(
        GitIndex $gitIndex,
        TrackedPath $specifedPath,
        PathType $specifedPathType,
        HashMap $targets,
        AddOptionAction $action
    ): GitIndex {
        $stagedEntries = ($this->stagedEntriesByPathService)($gitIndex, $specifedPath, $specifedPathType);

        throw_if(
            count($targets) === 0 && count($stagedEntries) === 0,
            new UseCaseException(sprintf(
                'fatal: pathspec \'%s\' did not match any files',
                $specifedPath->value
            ))
        );

        $gitIndex = $this->removeFromIndex($gitIndex, $targets, $stagedEntries);
        $gitIndex = $this->writeForIndex($gitIndex, $targets, $stagedEntries, $action);

        return $gitIndex;
    }

    /**
     * @param HashMap<TrackedPath> $targets
     * @param HashMap<IndexEntry> $stagedEntries
     */
    private function removeFromIndex(
        GitIndex $gitIndex,
        HashMap $targets,
        HashMap $stagedEntries,
    ): GitIndex {
        foreach ($stagedEntries as $stagedEntry) {
            if ($targets->exists($stagedEntry->trackedPath->value)) {
                continue;
            }

            $gitIndex->removeEntryByFilename($stagedEntry->trackedPath->value);
        }

        return $gitIndex;
    }

    /**
     * @param HashMap<TrackedPath> $targets
     * @param HashMap<IndexEntry> $stagedEntries
     */
    private function writeForIndex(
        GitIndex $gitIndex,
        HashMap $targets,
        HashMap $stagedEntries,
        AddOptionAction $action
    ): GitIndex {
        if (in_array($action, [AddOptionAction::All, AddOptionAction::Default], true)) {
            foreach ($targets as $target) {
                $objectHash = $this->hashObject($target);
                $gitIndex = $this->updateIndex($gitIndex, $target, $objectHash);
            }

            return $gitIndex;
        }

        // only staged files
        foreach ($stagedEntries as $stagedEntry) {
            if ($targets->exists($stagedEntry->trackedPath->value)) {
                $objectHash = $this->hashObject($stagedEntry->trackedPath);
                $gitIndex = $this->updateIndex($gitIndex, $stagedEntry->trackedPath, $objectHash);
            }
        }

        return $gitIndex;
    }

    private function hashObject(TrackedPath $trackedPath): ObjectHash
    {
        $contents = $this->fileRepository->getContents($trackedPath);
        $blobObject = BlobObject::new($contents);
        $objectHash = ObjectHash::new($blobObject->data);

        if (!$this->objectRepository->exists($objectHash)) {
            return $this->objectRepository->save($blobObject);
        }

        return $objectHash;
    }

    private function updateIndex(
        GitIndex $gitIndex,
        TrackedPath $target,
        ObjectHash $objectHash
    ) {
        $fileStat = $this->fileRepository->getStat($target);
        $indexEntry = IndexEntry::new($fileStat, $objectHash, $target);
        $gitIndex->addEntry($indexEntry);

        return $gitIndex;
    }
}
