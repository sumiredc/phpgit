<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\TrackedPath;
use Phpgit\Exception\UseCaseException;
use Phpgit\Request\HashObjectRequest;
use Throwable;

final class HashObjectUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly FileRepositoryInterface $fileRepository,
    ) {}

    public function __invoke(HashObjectRequest $request): Result
    {
        try {
            $trackedPath = try_or_throw(
                fn() => TrackedPath::parse($request->file),
                UseCaseException::class,
                sprintf('fatal: %s: \'%s\' is outside repository at \'%s\'', $request->file, $request->file, F_GIT_TRACKING_ROOT)
            );

            throw_unless(
                $this->fileRepository->exists($trackedPath),
                new UseCaseException(sprintf(
                    'fatal: could not open \'$s\' for reading: No such file or directory',
                    $request->file
                ))
            );

            $content = $this->fileRepository->getContents($trackedPath);
            $blobObject = BlobObject::new($content);
            $objectHash = ObjectHash::new($blobObject->data);

            $this->printer->writeln($objectHash->value);

            return Result::Success;
        } catch (UseCaseException $ex) {
            $this->printer->writeln($ex->getMessage());

            return Result::GitError;
        } catch (Throwable $th) {
            $this->printer->stackTrace($th);

            return Result::InternalError;
        }
    }
}
