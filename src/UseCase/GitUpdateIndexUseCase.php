<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\CommandInput\GitUpdateIndexOptionAction;
use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TrackingFile;
use Phpgit\Domain\UnixPermission;
use Phpgit\Exception\CannotAddIndexException;
use Phpgit\Exception\FileNotFoundException;
use Phpgit\Lib\IOInterface;
use Phpgit\Service\FileToHashService;
use RuntimeException;
use Throwable;

final class GitUpdateIndexUseCase
{
    public function __construct(
        private readonly IOInterface $io,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly FileRepositoryInterface $fileRepository,
        private readonly IndexRepositoryInterface $indexRepository,
    ) {}

    public function __invoke(
        GitUpdateIndexOptionAction $action,
        string $file,
        ?GitFileMode $gitFileMode,
        ?ObjectHash $objectHash
    ): Result {
        try {
            return match ($action) {
                GitUpdateIndexOptionAction::Add => $this->actionAdd($file),
                GitUpdateIndexOptionAction::Remove => $this->actionRemove($file),
                GitUpdateIndexOptionAction::ForceRemove => $this->actionForceRemove($file),
                GitUpdateIndexOptionAction::Cacheinfo => $this->actionCacheinfo($gitFileMode, $objectHash, $file),
            };
        } catch (FileNotFoundException) {
            $this->io->error([
                sprintf('error: %s: does not exist and --remove not passed', $file),
                sprintf('fatal: Unable to process path %s', $file)
            ]);

            return Result::GitError;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::GitError;
        }
    }

    /**
     * git update-index --add <file>
     * @see https://git-scm.com/docs/git-update-index#Documentation/git-update-index.txt---add
     */
    private function actionAdd(string $file): Result
    {
        $fileToHashService = new FileToHashService($this->fileRepository);
        [$trackingFile, $gitObject, $objectHash] = $fileToHashService($file);

        if (!$this->objectRepository->exists($objectHash)) {
            $this->objectRepository->save($gitObject);
        }

        $fileStat = $this->fileRepository->getStat($trackingFile);
        if (is_null($fileStat)) {
            throw new RuntimeException('failed to get stat');
        }

        $gitIndex = $this->indexRepository->getOrCreate();

        $indexEntry = IndexEntry::make($fileStat, $objectHash, $trackingFile);
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

        try {
            // NOTE: case of exists file
            if (!$this->indexRepository->exists()) {
                throw new CannotAddIndexException();
            }

            $gitIndex = $this->indexRepository->get();

            if (!$gitIndex->existsEntryByFilename($file)) {
                throw new CannotAddIndexException();
            }

            $fileToHashService = new FileToHashService($this->fileRepository);
            [$trackingFile, $gitObject, $objectHash] = $fileToHashService($file);

            if (!$this->objectRepository->exists($objectHash)) {
                $this->objectRepository->save($gitObject);
            }

            $fileStat = $this->fileRepository->getStat($trackingFile);
            if (is_null($fileStat)) {
                throw new RuntimeException('failed to get stat');
            }

            $indexEntry = IndexEntry::make($fileStat, $objectHash, $trackingFile);
            $gitIndex->addEntry($indexEntry);

            $this->indexRepository->save($gitIndex);

            return Result::Success;
        } catch (CannotAddIndexException) {
            $this->io->writeln([
                sprintf('error: %s: cannot add to the index - missing --add option?', $file),
                sprintf('fatal: Unable to process path %s', $file)
            ]);

            return Result::GitError;
        }
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
    private function actionCacheinfo(
        GitFileMode $gitFileMode,
        ObjectHash $objectHash,
        string $file
    ): Result {
        if (!$this->fileRepository->existsByFilename($file)) {
            $this->io->writeln([
                sprintf('error: %s: cannot add to the index - missing --add option?', $file),
                sprintf('fatal: git update-index: --cacheinfo cannot add %s', $file)
            ]);

            return Result::GitError;
        }

        $gitIndex = $this->indexRepository->getOrCreate();

        $trackingFile = TrackingFile::new($file);
        $fileStat = FileStat::newForCacheinfo($gitFileMode->fileStatMode());

        $indexEntry = IndexEntry::make($fileStat, $objectHash, $trackingFile);
        $gitIndex->addEntry($indexEntry);

        $this->indexRepository->save($gitIndex);

        return Result::Success;
    }
}
