<?php

namespace Phpgit\UseCase;

use Phpgit\Domain\CommandArgument\GitCatFileTypeArgument;
use Phpgit\Domain\GitPath;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Throwable;

final class GitCatFileUseCase
{
    public function __construct(
        private readonly StyleInterface&OutputInterface $io,
        private readonly LoggerInterface $logger,
        private readonly GitPath $gitPath,
        private readonly ObjectRepositoryInterface $objectRepository,
    ) {}

    public function __invoke(bool $type, bool $size, bool $exists, bool $prettyPrint, string $object): Result
    {
        $action = match (true) {
            $type => GitCatFileTypeArgument::Type,
            $size => GitCatFileTypeArgument::Size,
            $exists => GitCatFileTypeArgument::Exists,
            $prettyPrint => GitCatFileTypeArgument::PrettyPrint,
            default => null,
        };
        if (is_null($action)) {
            $this->io->warning("missing required type option");

            return Result::Invalid;
        }

        $objectHash = ObjectHash::parse($object);
        if (is_null($objectHash)) {
            $this->io->warning(sprintf("Invalid argument in object: %s", $object));

            return Result::Invalid;
        }

        return match ($action) {
            GitCatFileTypeArgument::Type => $this->actionType($objectHash),
            GitCatFileTypeArgument::Size => $this->actionSize($objectHash),
            GitCatFileTypeArgument::Exists => $this->actionExists($objectHash),
            GitCatFileTypeArgument::PrettyPrint => $this->actionPrettyPrint($objectHash),
        };
    }

    private function actionType(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->existObject($objectHash)) {
            $this->io->warning(sprintf('git cat-file: could not get object info: %s', $objectHash->value()));

            return Result::Invalid;
        }

        try {
            $gitObject = $this->objectRepository->getObject($objectHash);
            $this->io->success($gitObject->objectType->value);

            return Result::Success;
        } catch (Throwable $th) {
            $this->logger->error('failed to getObject', ['exception' => $th]);

            return Result::Failure;
        }
    }

    private function actionSize(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->existObject($objectHash)) {
            $this->io->warning(sprintf('git cat-file: could not get object info: %s', $objectHash->value()));

            return Result::Invalid;
        }

        try {
            $gitObject = $this->objectRepository->getObject($objectHash);
            $this->io->success($gitObject->size);

            return Result::Success;
        } catch (Throwable $th) {
            $this->logger->error('failed to getObject', ['exception' => $th]);

            return Result::Failure;
        }
    }

    private function actionExists(ObjectHash $objectHash): Result
    {
        if ($this->objectRepository->existObject($objectHash)) {
            $this->io->success('exist object');

            return Result::Success;
        }

        $this->io->note('don\'t exists object');

        return Result::Failure;
    }

    private function actionPrettyPrint(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->existObject($objectHash)) {
            $this->io->warning(sprintf('git cat-file: could not get object info: %s', $objectHash->value()));

            return Result::Invalid;
        }

        try {
            $gitObject = $this->objectRepository->getObject($objectHash);
            $this->io->write($gitObject->body);

            return Result::Success;
        } catch (Throwable $th) {
            $this->logger->error('failed to getObject', ['exception' => $th]);

            return Result::Failure;
        }
    }
}
