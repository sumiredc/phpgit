<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\UseCaseException;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\CommitTreeRequest;
use Phpgit\Service\CreateCommitTreeServiceInterface;
use Throwable;

final class CommitTreeUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly CreateCommitTreeServiceInterface $createCommitTreeService
    ) {}

    public function __invoke(CommitTreeRequest $request): Result
    {
        try {
            $treeHash = try_or_throw(
                fn() => ObjectHash::parse($request->tree),
                UseCaseException::class,
                (sprintf('fatal: not a valid object name %s', $request->tree))
            );

            $parentHash = try_or_throw(
                fn() => $request->parent === '' ? null : ObjectHash::parse($request->parent),
                UseCaseException::class,
                sprintf('fatal: not a valid object name %s', $request->parent)
            );

            throw_unless(
                $this->objectRepository->exists($treeHash),
                new UseCaseException(sprintf('fatal: %s is not a valid object', $request->tree))
            );

            throw_if(
                !is_null($parentHash) && !$this->objectRepository->exists($parentHash),
                new UseCaseException(sprintf('fatal: %s is not a valid object', $request->parent))
            );

            $gitObject = $this->objectRepository->get($treeHash);
            throw_unless(
                $gitObject->objectType === ObjectType::Tree,
                new UseCaseException(sprintf('fatal: %s is not a valid \'tree\' object', $request->tree))
            );

            $commit = ($this->createCommitTreeService)($treeHash, $request->message, $parentHash);
            $commitHash = $this->objectRepository->save($commit);

            $this->printer->writeln($commitHash->value);

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
