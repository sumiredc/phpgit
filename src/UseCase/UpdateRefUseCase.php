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
use Phpgit\Service\ResolveRevisionServiceInterface;
use Throwable;

final class UpdateRefUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly RefRepositoryInterface $refRepository,
        private readonly ResolveRevisionServiceInterface $resolveRevisionService,
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

        $newObject = ($this->resolveRevisionService)($newValue);
        throw_if(
            is_null($newObject),
            new UseCaseException(sprintf('fatal: %s: not a valid SHA1', $newValue))
        );

        throw_unless(
            $this->objectRepository->exists($newObject),
            new UseCaseException(sprintf(
                'fatal: update_ref failed for ref \'%s\': cannot update ref \'%s\': trying to write ref \'%s\' with nonexistent object %s',
                $refValue,
                $ref->path,
                $ref->path,
                $newObject->value,
            ))
        );

        if ($oldValue === '') {
            $this->refRepository->createOrUpdate($ref, $newObject);

            return Result::Success;
        }

        // strict mode: need to expect old value

        $oldObject = ($this->resolveRevisionService)($oldValue);
        throw_if(
            is_null($oldObject),
            new UseCaseException(sprintf('fatal: %s: not a valid SHA1', $oldValue))
        );

        throw_unless(
            $this->objectRepository->exists($oldObject),
            new UseCaseException(sprintf(
                'fatal: update_ref failed for ref \'%s\': cannot update ref \'%s\': trying to write ref \'%s\' with nonexistent object %s',
                $refValue,
                $ref->path,
                $ref->path,
                $oldObject->value,
            ))
        );

        $currentObject = $this->refRepository->resolve($ref);
        throw_unless(
            $currentObject->value === $oldObject->value,
            new UseCaseException(sprintf(
                'fatal: update_ref failed for ref \'%s\': cannot lock ref \'%s\': is at %s but expected %s',
                $refValue,
                $refValue,
                $currentObject->value,
                $oldValue,
            ))
        );

        $this->refRepository->update($ref, $newObject);

        return Result::Success;
    }

    private function actionDelete(string $refValue, string $oldValue): Result
    {
        $ref = $this->refRepository->dereference($refValue);
        throw_if(
            is_null($ref),
            new UseCaseException(sprintf('error: refusing to update ref with bad name \'%s\'', $refValue))
        );

        if ($oldValue === '') {
            if ($this->refRepository->exists($ref)) {
                $this->refRepository->delete($ref);
            }

            return Result::Success;
        }

        // strict mode: need to expect old value

        $oldObject = ($this->resolveRevisionService)($oldValue);
        throw_if(
            is_null($oldObject),
            new UseCaseException(sprintf('fatal: %s: not a valid SHA1', $oldValue))
        );

        throw_unless(
            $this->refRepository->exists($ref),
            new UseCaseException(sprintf(
                'error: cannot lock ref \'%s\': unable to resolve reference \'%s\'',
                $refValue,
                $refValue
            ))
        );

        $currentObject = $this->refRepository->resolve($ref);
        throw_unless(
            $currentObject->value === $oldObject->value,
            new UseCaseException(sprintf(
                'error: cannot lock ref \'%s\': is at %s but expected %s',
                $refValue,
                $currentObject->value,
                $oldObject->value,
            ))
        );

        $this->refRepository->delete($ref);

        return Result::Success;
    }
}
