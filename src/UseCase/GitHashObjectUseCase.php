<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\FileNotFoundException;
use Phpgit\Service\FileToObjectService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Throwable;

final class GitHashObjectUseCase
{
    public function __construct(
        private readonly StyleInterface&OutputInterface $io,
        private readonly LoggerInterface $logger,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly FileRepositoryInterface $fileRepository,
    ) {}

    public function __invoke(string $file): Result
    {
        $fileToObjectService = new FileToObjectService($this->fileRepository);

        try {
            [$trakingFile, $gitObject] = $fileToObjectService($file);
            $objectHash = $this->objectRepository->save($gitObject);
            $this->io->success($objectHash->value);

            return Result::Success;
        } catch (FileNotFoundException) {
            $this->io->error(sprintf('file not found: %s', $file));

            return Result::Invalid;
        } catch (Throwable $th) {
            $this->logger->error('failed to create hash object', ['exception' => $th]);

            return Result::Failure;
        }
    }
}
