<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use InvalidArgumentException;
use Phpgit\Domain\CommandInput\CatFileOptionType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\CannotGetObjectInfoException;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\CatFileRequest;
use Throwable;

final class CatFileUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly ObjectRepositoryInterface $objectRepository,
    ) {}

    public function __invoke(CatFileRequest $request): Result
    {
        try {
            $objectHash = ObjectHash::parse($request->object);

            return match ($request->type) {
                CatFileOptionType::Type => $this->actionType($objectHash),
                CatFileOptionType::Size => $this->actionSize($objectHash),
                CatFileOptionType::Exists => $this->actionExists($objectHash),
                CatFileOptionType::PrettyPrint => $this->actionPrettyPrint($objectHash),
            };
        } catch (InvalidArgumentException) {
            $this->printer->writeln(sprintf("fatal: Not a valid object name %s", $request->object));

            return Result::GitError;
        } catch (CannotGetObjectInfoException) {
            $this->printer->writeln('fatal: git cat-file: could not get object info');

            return Result::GitError;
        } catch (Throwable $th) {
            $this->printer->stackTrace($th);

            return Result::InternalError;
        }
    }

    /**
     * git cat-file -t <object>
     */
    private function actionType(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            throw new CannotGetObjectInfoException;
        }

        $gitObject = $this->objectRepository->get($objectHash);
        $this->printer->writeln($gitObject->objectType->value);

        return Result::Success;
    }

    /**
     * git cat-file -s <object>
     */
    private function actionSize(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            throw new CannotGetObjectInfoException;
        }

        $gitObject = $this->objectRepository->get($objectHash);
        $this->printer->writeln(strval($gitObject->size));

        return Result::Success;
    }

    /**
     * git cat-file -e <object>
     */
    private function actionExists(ObjectHash $objectHash): Result
    {
        if ($this->objectRepository->exists($objectHash)) {
            return Result::Success;
        }

        return Result::Failure;
    }

    /**
     * git cat-file -p <object>
     */
    private function actionPrettyPrint(ObjectHash $objectHash): Result
    {
        if (!$this->objectRepository->exists($objectHash)) {
            throw new InvalidArgumentException;
        }

        $gitObject = $this->objectRepository->get($objectHash);
        $this->printer->write($gitObject->prettyPrint());

        return Result::Success;
    }
}
