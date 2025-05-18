<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\CommandInput\UpdateRefOptionAction;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\UseCaseException;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\UpdateRefRequest;
use Phpgit\Service\ResolveRevisionService;
use Throwable;

final class UpdateRefUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly RefRepositoryInterface $refRepository,
    ) {}

    public function __invoke(UpdateRefRequest $request): Result
    {
        try {
            return match ($request->action) {
                UpdateRefOptionAction::Update => $this->actionUpdate(
                    $request->ref,
                    $request->newValue,
                    $request->oldValue,
                ),
                UpdateRefOptionAction::Delete => $this->actionDelete(
                    $request->ref,
                    $request->oldValue,
                ),
            };
        } catch (UseCaseException $ex) {
            $this->printer->writeln($ex->getMessage());

            return Result::GitError;
        } catch (Throwable $th) {
            $this->printer->stackTrace($th);

            return Result::InternalError;
        }
    }

    private function actionUpdate(string $refValue, string $newValue, string $oldValue): Result
    {
        $ref = $this->refRepository->dereference($refValue);
        if (is_null($ref)) {
            return Result::Success;
        }

        $service = new ResolveRevisionService($this->refRepository);
        $newObject = $service($newValue);
        if (is_null($newObject)) {
            throw new UseCaseException(sprintf('fatal: %s: not a valid SHA1', $newValue));
        }

        if (!$this->objectRepository->exists($newObject)) {
            throw new UseCaseException(sprintf(
                'fatal: update_ref failed for ref \'%s\': cannot update ref \'%s\': trying to write ref \'%s\' with nonexistent object %s',
                $refValue,
                $ref->path,
                $ref->path,
                $newObject->value,
            ));
        }

        if ($oldValue === '') {
            $this->refRepository->createOrUpdate($ref, $newObject);

            return Result::Success;
        }

        // need to expect old value

        $oldObject = $service($oldValue);
        if (is_null($oldObject)) {
            throw new UseCaseException(sprintf('fatal: %s: not a valid SHA1', $oldValue));
        }

        if (!$this->objectRepository->exists($oldObject)) {
            throw new UseCaseException(sprintf(
                'fatal: update_ref failed for ref \'%s\': cannot update ref \'%s\': trying to write ref \'%s\' with nonexistent object %s',
                $refValue,
                $ref->path,
                $ref->path,
                $oldObject->value,
            ));
        }

        $currentObject = $this->refRepository->resolve($ref);
        if ($currentObject->value !== $oldObject->value) {
            throw new UseCaseException(sprintf(
                'fatal: update_ref failed for ref \'%s\': cannot lock ref \'%s\': is at %s but expected %s',
                $refValue,
                $refValue,
                $currentObject->value,
                $oldValue,
            ));
        }

        $this->refRepository->update($ref, $newObject);

        return Result::Success;
    }

    private function actionDelete(string $refValue, string $oldValue): Result
    {
        $ref = $this->refRepository->dereference($refValue);
        if (is_null($ref)) {
            throw new UseCaseException(sprintf('error: refusing to update ref with bad name \'%s\'', $refValue));
        }

        if ($oldValue === '') {
            if ($this->refRepository->exists($ref)) {
                $this->refRepository->delete($ref);
            }

            return Result::Success;
        }

        // need to expect old value

        $service = new ResolveRevisionService($this->refRepository);
        $oldObject = $service($oldValue);
        if (is_null($oldObject)) {
            throw new UseCaseException(sprintf('fatal: %s: not a valid SHA1', $oldValue));
        }

        if (!$this->refRepository->exists($ref)) {
            throw new UseCaseException(sprintf(
                'error: cannot lock ref \'%s\': unable to resolve reference \'%s\'',
                $refValue,
                $refValue
            ));
        }

        $currentObject = $this->refRepository->resolve($ref);
        if ($currentObject->value !== $oldObject->value) {
            throw new UseCaseException(sprintf(
                'error: cannot lock ref \'%s\': is at %s but expected %s',
                $refValue,
                $currentObject->value,
                $oldObject->value,
            ));
        }

        $this->refRepository->delete($ref);

        return Result::Success;
    }
}
