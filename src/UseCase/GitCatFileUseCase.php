<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use InvalidArgumentException;
use Phpgit\Domain\CommandInput\GitCatFileOptionType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Lib\IOInterface;
use Throwable;

final class GitCatFileUseCase
{
    public function __construct(
        private readonly IOInterface $io,
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
            $this->io->warning(sprintf("invalid argument in object: %s", $object));

            return Result::Invalid;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::Failure;
        }
    }

    private function actionType(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            $this->io->warning(sprintf('could not get object info: %s', $objectHash->value()));

            return Result::Invalid;
        }

        $gitObject = $this->objectRepository->get($objectHash);
        $this->io->success($gitObject->objectType->value);

        return Result::Success;
    }

    private function actionSize(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            $this->io->warning(sprintf('could not get object info: %s', $objectHash->value()));

            return Result::Invalid;
        }

        $gitObject = $this->objectRepository->get($objectHash);
        $this->io->success(strval($gitObject->size));

        return Result::Success;
    }

    private function actionExists(ObjectHash $objectHash): Result
    {
        if ($this->objectRepository->exists($objectHash)) {
            $this->io->success('exist object');

            return Result::Success;
        }

        $this->io->text('don\'t exists object');

        return Result::Failure;
    }

    private function actionPrettyPrint(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            $this->io->warning(sprintf('could not get object info: %s', $objectHash->value()));

            return Result::Invalid;
        }

        $gitObject = $this->objectRepository->get($objectHash);
        $this->io->write($gitObject->body);

        return Result::Success;
    }
}
