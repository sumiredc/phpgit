<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\CommandInput\GitUpdateIndexOptionAction;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
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

    public function __invoke(GitUpdateIndexOptionAction $action, string $file): Result
    {
        try {
            return match ($action) {
                GitUpdateIndexOptionAction::Add => $this->actionAdd($file),
                GitUpdateIndexOptionAction::Remove => $this->actionRemove($file),
                GitUpdateIndexOptionAction::ForceRemove => $this->actionForceRemove(),
                GitUpdateIndexOptionAction::Replace => $this->actionReplace(),
            };
        } catch (CannotAddIndexException) { {
                $this->io->writeln([
                    sprintf('error: %s: cannot add to the index - missing --add option?', $file),
                    sprintf('fatal: Unable to process path %s', $file)
                ]);

                return Result::Success; // NOTE: treat as normal end
            }
        } catch (FileNotFoundException) {
            $this->io->error(sprintf('file not found: %s', $file));

            return Result::Invalid;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::Failure;
        }
    }

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

    private function actionRemove(string $file): Result
    {
        // NOTE: case of exists file
        if ($this->fileRepository->existsByFilename($file)) {
            $this->actionRemoveCaseOfExistsFile($file);
        } else {
            $this->actionRemoveCaseOfDontExistsFile($file);
        }

        return Result::Success;
    }

    /**
     * NOTE: 
     * 
     * case of not found index
     *  -> alert
     * 
     * case of don't registed index
     *  -> alert
     */
    private function actionRemoveCaseOfExistsFile(string $file): void
    {
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
    }

    private function actionRemoveCaseOfDontExistsFile(string $file): void
    {
        if (!$this->indexRepository->exists()) {
            $gitIndex = $this->indexRepository->get();
            $gitIndex->removeEntryByFilename($file);
        }
    }

    private function actionForceRemove(): Result
    {
        return Result::Success;
    }

    private function actionReplace(): Result
    {
        return Result::Success;
    }
}
