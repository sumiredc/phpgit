<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use InvalidArgumentException;
use Phpgit\Domain\BlobObject;
use Phpgit\Domain\CommandInput\UpdateIndexOptionAction;
use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TrackingFile;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Exception\UseCaseException;
use Phpgit\Request\UpdateIndexRequest;
use Throwable;

final class UpdateIndexUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly FileRepositoryInterface $fileRepository,
        private readonly IndexRepositoryInterface $indexRepository,
    ) {}

    public function __invoke(UpdateIndexRequest $request): Result
    {
        try {
            return match ($request->action) {
                UpdateIndexOptionAction::Add => $this->actionAdd($request->file),
                UpdateIndexOptionAction::Remove => $this->actionRemove($request->file),
                UpdateIndexOptionAction::ForceRemove => $this->actionForceRemove($request->file),
                UpdateIndexOptionAction::Cacheinfo => $this->actionCacheinfo(
                    $request->mode, // never null
                    $request->object, // never null
                    $request->file
                ),
            };
        } catch (UseCaseException $ex) {
            $this->printer->writeln($ex->getMessage());

            return Result::GitError;
        } catch (Throwable $th) {
            $this->printer->stackTrace($th);

            return Result::InternalError;
        }
    }

    /**
     * git update-index --add <file>
     * @see https://git-scm.com/docs/git-update-index#Documentation/git-update-index.txt---add
     */
    private function actionAdd(string $file): Result
    {
        $trackingFile = TrackingFile::new($file);
        throw_unless(
            $this->fileRepository->exists($trackingFile),
            new UseCaseException(sprintf(
                "error: %s: does not exist and --remove not passed\nfatal: Unable to process path %s",
                $file,
                $file
            ))
        );

        $content = $this->fileRepository->getContents($trackingFile);
        $blobObject = BlobObject::new($content);
        $objectHash = ObjectHash::new($blobObject->data);

        if (!$this->objectRepository->exists($objectHash)) {
            $this->objectRepository->save($blobObject);
        }

        $fileStat = $this->fileRepository->getStat($trackingFile);

        $gitIndex = $this->indexRepository->getOrCreate();

        $indexEntry = IndexEntry::new($fileStat, $objectHash, $trackingFile);
        $gitIndex->addEntry($indexEntry);

        $this->indexRepository->save($gitIndex);

        return Result::Success;
    }

    /**
     * git update-index --remove <file>
     * @see https://git-scm.com/docs/git-update-index#Documentation/git-update-index.txt---remove
     */
    private function actionRemove(string $file): Result
    {
        if (!$this->fileRepository->existsByFilename($file)) {
            // NOTE: case of don't exists file -> force-remove
            return $this->actionForceRemove($file);
        }

        // NOTE: case of exists file
        throw_unless(
            $this->indexRepository->exists(),
            new UseCaseException(sprintf(
                "error: %s: cannot add to the index - missing --add option?\nfatal: Unable to process path %s",
                $file,
                $file
            ))
        );

        $gitIndex = $this->indexRepository->get();
        throw_unless(
            $gitIndex->existsEntryByFilename($file),
            new UseCaseException(sprintf(
                "error: %s: cannot add to the index - missing --add option?\nfatal: Unable to process path %s",
                $file,
                $file
            ))
        );

        $trackingFile = TrackingFile::new($file);
        throw_unless(
            $this->fileRepository->exists($trackingFile),
            new UseCaseException(sprintf(
                "error: %s: does not exist and --remove not passed\nfatal: Unable to process path %s",
                $file,
                $file
            ))
        );

        $content = $this->fileRepository->getContents($trackingFile);
        $blobObject = BlobObject::new($content);
        $objectHash = ObjectHash::new($blobObject->data);

        if (!$this->objectRepository->exists($objectHash)) {
            $this->objectRepository->save($blobObject);
        }

        $fileStat = $this->fileRepository->getStat($trackingFile);
        $indexEntry = IndexEntry::new($fileStat, $objectHash, $trackingFile);
        $gitIndex->addEntry($indexEntry);

        $this->indexRepository->save($gitIndex);

        return Result::Success;
    }

    /**
     * git update-index --force-remove <file>
     * @see https://git-scm.com/docs/git-update-index#Documentation/git-update-index.txt---force-remove
     */
    private function actionForceRemove(string $file): Result
    {
        if (!$this->indexRepository->exists()) {
            return Result::Success;
        }

        $gitIndex = $this->indexRepository->get();
        $gitIndex->removeEntryByFilename($file);
        $this->indexRepository->save($gitIndex);

        return Result::Success;
    }

    /**
     * git update-index --cacheinfo <mode> <object> <file>
     * @see https://git-scm.com/docs/git-update-index#Documentation/git-update-index.txt---cacheinfoltmodegtltobjectgtltpathgt-1
     */
    private function actionCacheinfo(string $mode, string $object, string $file): Result
    {
        $gitFileMode = try_or_throw(
            fn() => GitFileMode::from($mode),
            UseCaseException::class,
            sprintf('fatal: git update-index: --cacheinfo cannot add %s', $mode)
        );

        $objectHash = try_or_throw(
            fn() => ObjectHash::parse($object),
            UseCaseException::class,
            sprintf('fatal: git update-index: --cacheinfo cannot add %s', $object)
        );

        throw_unless(
            $this->fileRepository->existsByFilename($file),
            new UseCaseException(sprintf(
                "error: %s: cannot add to the index - missing --add option?\nfatal: git update-index: --cacheinfo cannot add %s",
                $file,
                $file
            ))
        );

        $gitIndex = $this->indexRepository->getOrCreate();

        $trackingFile = TrackingFile::new($file);
        $fileStat = FileStat::newForCacheinfo($gitFileMode->fileStatMode());

        $indexEntry = IndexEntry::new($fileStat, $objectHash, $trackingFile);
        $gitIndex->addEntry($indexEntry);

        $this->indexRepository->save($gitIndex);

        return Result::Success;
    }
}
