<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\FileNotFoundException;
use Phpgit\Lib\IOInterface;
use Phpgit\Service\FileToHashService;
use Throwable;

final class GitHashObjectUseCase
{
    public function __construct(
        private readonly IOInterface $io,
        private readonly FileRepositoryInterface $fileRepository,
    ) {}

    public function __invoke(string $file): Result
    {
        $fileToHashService = new FileToHashService($this->fileRepository);

        try {
            [$trakingFile, $gitObject, $objectHash] = $fileToHashService($file);
            $this->io->writeln($objectHash->value);

            return Result::Success;
        } catch (FileNotFoundException) {
            $this->io->writeln(sprintf('fatal: could not open \'$s\' for reading: No such file or directory', $file));

            return Result::GitError;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::GitError;
        }
    }
}
