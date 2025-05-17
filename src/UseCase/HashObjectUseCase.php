<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\FileNotFoundException;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\HashObjectRequest;
use Phpgit\Service\FileToHashService;
use Throwable;

final class HashObjectUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly FileRepositoryInterface $fileRepository,
    ) {}

    public function __invoke(HashObjectRequest $request): Result
    {
        $fileToHashService = new FileToHashService($this->fileRepository);

        try {
            [$trakingFile, $gitObject, $objectHash] = $fileToHashService($request->file);
            $this->printer->writeln($objectHash->value);

            return Result::Success;
        } catch (FileNotFoundException) {
            $this->printer->writeln(
                sprintf(
                    'fatal: could not open \'$s\' for reading: No such file or directory',
                    $request->file
                )
            );

            return Result::GitError;
        } catch (Throwable $th) {
            $this->printer->stackTrace($th);

            return Result::InternalError;
        }
    }
}
