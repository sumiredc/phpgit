<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use InvalidArgumentException;
use Phpgit\Domain\CommandInput\GitCatFileOptionType;
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
        private readonly ObjectRepositoryInterface $objectRepository,
    ) {}

    public function __invoke(GitCatFileOptionType $type, string $object): Result
    {
        try {
            $objectHash = ObjectHash::parse($object);

            return match ($type) {
                GitCatFileOptionType::Type => $this->actionType($objectHash),
                GitCatFileOptionType::Size => $this->actionSize($objectHash),
                GitCatFileOptionType::Exists => $this->actionExists($objectHash),
                GitCatFileOptionType::PrettyPrint => $this->actionPrettyPrint($objectHash),
            };
        } catch (InvalidArgumentException) {
            $this->io->warning(sprintf("Invalid argument in object: %s", $object));

            return Result::Invalid;
        } catch (Throwable $th) {
            $this->logger->error('failed to cat file', ['exception' => $th]);

            return Result::Failure;
        }
    }

    private function actionType(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            $this->io->warning(sprintf('git cat-file: could not get object info: %s', $objectHash->value()));

            return Result::Invalid;
        }

        try {
            $gitObject = $this->objectRepository->get($objectHash);
            $this->io->success($gitObject->objectType->value);

            return Result::Success;
        } catch (Throwable $th) {
            $this->logger->error('failed to getObject', ['exception' => $th]);

            return Result::Failure;
        }
    }

    private function actionSize(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            $this->io->warning(sprintf('git cat-file: could not get object info: %s', $objectHash->value()));

            return Result::Invalid;
        }

        try {
            $gitObject = $this->objectRepository->get($objectHash);
            $this->io->success(strval($gitObject->size));

            return Result::Success;
        } catch (Throwable $th) {
            $this->logger->error('failed to getObject', ['exception' => $th]);

            return Result::Failure;
        }
    }

    private function actionExists(ObjectHash $objectHash): Result
    {
        if ($this->objectRepository->exists($objectHash)) {
            $this->io->success('exist object');

            return Result::Success;
        }

        $this->io->note('don\'t exists object');

        return Result::Failure;
    }

    private function actionPrettyPrint(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            $this->io->warning(sprintf('git cat-file: could not get object info: %s', $objectHash->value()));

            return Result::Invalid;
        }

        try {
            $gitObject = $this->objectRepository->get($objectHash);
            $this->io->write($gitObject->body);

            return Result::Success;
        } catch (Throwable $th) {
            $this->logger->error('failed to getObject', ['exception' => $th]);

            return Result::Failure;
        }
    }
}
