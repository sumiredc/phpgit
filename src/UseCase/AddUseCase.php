<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\CommandInput\AddOptionAction;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TrackedPath;
use Phpgit\Exception\UseCaseException;
use Phpgit\Request\AddRequest;
use Throwable;

final class AddUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly FileRepositoryInterface $fileRepository,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly IndexRepositoryInterface $indexRepository,
    ) {}

    public function __invoke(AddRequest $request): Result
    {
        $path = $request->action === AddOptionAction::All
            ? '.'
            : $request->path;

        try {
            throw_if(
                $this->fileRepository->isOutsideRepository($path),
                new UseCaseException(sprintf(
                    'fatal: %s: \'%s\' is outside repository at \'%s\'',
                    $request->path,
                    $request->path,
                    F_GIT_TRACKING_ROOT
                ))
            );

            $targets = $this->searchTarget($path);
            $gitIndex = $this->indexRepository->getOrCreate();

            foreach ($targets as $target) {
                $hash = $this->hashObject($target);
                $this->updateIndex($gitIndex, $target, $hash);
            }

            return Result::Success;
        } catch (UseCaseException $ex) {
            $this->printer->writeln($ex->getMessage());

            return Result::GitError;
        } catch (Throwable $th) {
            $this->printer->stackTrace($th);

            return Result::InternalError;
        }
    }

    /** @return array<TrackedPath> */
    private function searchTarget(string $path): array
    {
        $targets = $this->fileRepository->search($path);

        throw_if(
            empty($targets),
            new UseCaseException(sprintf('fatal: pathspec \'%s\' did not match any files', $path))
        );

        return $targets;
    }

    private function hashObject(TrackedPath $trackedPath): ObjectHash
    {
        $content = $this->fileRepository->getContents($trackedPath);
        $blobObject = BlobObject::new($content);
        $objectHash = ObjectHash::new($blobObject->data);

        if (!$this->objectRepository->exists($objectHash)) {
            $this->objectRepository->save($blobObject);
        }

        return $objectHash;
    }

    private function updateIndex(
        GitIndex $gitIndex,
        TrackedPath $trackedPath,
        ObjectHash $objectHash
    ): void {
        $fileStat = $this->fileRepository->getStat($trackedPath);
        $indexEntry = IndexEntry::new($fileStat, $objectHash, $trackedPath);
        $gitIndex->addEntry($indexEntry);

        $this->indexRepository->save($gitIndex);
    }
}
