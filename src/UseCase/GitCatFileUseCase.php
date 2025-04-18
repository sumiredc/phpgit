<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use InvalidArgumentException;
use Phpgit\Domain\CommandInput\GitCatFileOptionType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\CannotGetObjectInfoException;
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
            $this->io->writeln(sprintf("fatal: Not a valid object name %s", $object));

            return Result::GitError;
        } catch (CannotGetObjectInfoException) {
            $this->io->writeln('fatal: git cat-file: could not get object info');

            return Result::GitError;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::GitError;
        }
    }

    private function actionType(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            throw new CannotGetObjectInfoException;
        }

        $gitObject = $this->objectRepository->get($objectHash);
        $this->io->writeln($gitObject->objectType->value);

        return Result::Success;
    }

    private function actionSize(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            throw new CannotGetObjectInfoException;
        }

        $gitObject = $this->objectRepository->get($objectHash);
        $this->io->writeln(strval($gitObject->size));

        return Result::Success;
    }

    private function actionExists(ObjectHash $objectHash): Result
    {
        if ($this->objectRepository->exists($objectHash)) {
            return Result::Success;
        }

        return Result::Failure;
    }

    private function actionPrettyPrint(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            new InvalidArgumentException;
        }

        $gitObject = $this->objectRepository->get($objectHash);
        $this->io->write($gitObject->body);

        return Result::Success;
    }
}
