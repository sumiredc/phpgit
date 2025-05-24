<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\CommandInput\AddOptionAction;
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
            ? F_GIT_TRACKING_ROOT
            : $request->path;

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

            $targets = $this->searchTarget($trackedPath);
            $gitIndex = $this->indexRepository->getOrCreate();

            foreach ($targets as $target) {
                $objectHash = $this->hashObject($target);
                $fileStat = $this->fileRepository->getStat($target);
                $indexEntry = IndexEntry::new($fileStat, $objectHash, $target);
                $gitIndex->addEntry($indexEntry);
            }

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

    /** @return array<TrackedPath> */
    private function searchTarget(TrackedPath $trackedPath): array
    {
        $targets = $this->fileRepository->search($trackedPath);

        throw_if(
            empty($targets),
            new UseCaseException(sprintf('fatal: pathspec \'%s\' did not match any files', $trackedPath->value))
        );

        return $targets;
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
}
