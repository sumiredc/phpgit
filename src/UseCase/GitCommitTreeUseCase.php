<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\CommitObject;
use Phpgit\Domain\GitSignature;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Repository\GitConfigRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Timestamp;
use Phpgit\Exception\UseCaseException;
use Phpgit\Lib\IOInterface;
use Phpgit\Request\GitCommitTreeRequest;
use Throwable;

final class GitCommitTreeUseCase
{
    public function __construct(
        private readonly IOInterface $io,
        private readonly GitConfigRepositoryInterface $gitConfigRepository,
        private readonly ObjectRepositoryInterface $objectRepository,
    ) {}

    public function __invoke(GitCommitTreeRequest $request): Result
    {
        try {
            $treeHash = ObjectHash::tryParse($request->tree);
            if (is_null($treeHash)) {
                throw new UseCaseException('fatal: not a valid object name %s', $request->tree);
            }

            $parentHash = ObjectHash::tryParse($request->parent);
            if ($request->parent !== '' && is_null($parentHash)) {
                throw new UseCaseException('fatal: not a valid object name %s', $request->parent);
            }

            if (!$this->objectRepository->exists($treeHash)) {
                throw new UseCaseException('fatal: %s is not a valid object', $request->tree);
            }

            if (!is_null($parentHash) && !$this->objectRepository->exists($parentHash)) {
                throw new UseCaseException('fatal: %s is not a valid object', $request->parent);
            }

            $gitObject = $this->objectRepository->get($treeHash);
            if ($gitObject->objectType !== ObjectType::Tree) {
                throw new UseCaseException('fatal: %s is not a valid \'tree\' object', $request->tree);
            }

            $commitHash = $this->createCommitTree($treeHash, $request->message, $parentHash);

            $this->io->writeln($commitHash->value);

            return Result::Success;
        } catch (UseCaseException $ex) {
            $this->io->writeln($ex->getMessage());

            return Result::GitError;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::InternalError;
        }
    }

    private function createCommitTree(
        ObjectHash $treetHash,
        string $message,
        ?ObjectHash $parentHash,
    ): ObjectHash {
        $gitConfig = $this->gitConfigRepository->get();

        $timestamp = Timestamp::new();
        $author = GitSignature::new($gitConfig->userName, $gitConfig->userEmail, $timestamp);
        $committer = GitSignature::new($gitConfig->userName, $gitConfig->userEmail, $timestamp);

        $commit = CommitObject::new($treetHash, $author, $committer, $message, $parentHash);

        return $this->objectRepository->save($commit);
    }
}
