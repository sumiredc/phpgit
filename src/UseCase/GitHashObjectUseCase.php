<?php

namespace Phpgit\UseCase;

use Phpgit\Domain\GitPath;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Throwable;

final class GitHashObjectUseCase
{
    public function __construct(
        private readonly StyleInterface&OutputInterface $io,
        private readonly LoggerInterface $logger,
        private readonly GitPath $gitPath,
        private readonly ObjectRepositoryInterface $objectRepository,
    ) {}

    public function __invoke(string $file): Result
    {
        $filePath = sprintf('%s/%s', $this->gitPath->trackingRoot, $file);
        if (!file_exists($filePath)) {
            $this->io->error(sprintf('File not found: %s', $file));

            return Result::Invalid;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->logger->error(sprintf('failed to file_get_contents: %s', $filePath));

            return Result::Failure;
        }

        $header = sprintf('blob %d\0', strlen($content));
        $object = sprintf('%s%s', $header, $content);

        try {
            $objectHash = $this->objectRepository->saveObject($object);
            $this->io->success($objectHash->value);

            return Result::Success;
        } catch (Throwable $th) {
            $this->logger->error('failed to saveObject', ['exception' => $th]);

            return Result::Failure;
        }
    }
}
