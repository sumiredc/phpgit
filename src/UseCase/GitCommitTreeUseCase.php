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
use Phpgit\Exception\InvalidObjectException;
use Phpgit\Exception\InvalidObjectNameException;
use Phpgit\Exception\InvalidObjectTypeException;
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
            $objectHash = ObjectHash::tryParse($request->tree);
            if (is_null($objectHash)) {
                throw new InvalidObjectNameException(
                    sprintf('fatal: not a valid object name %s', $request->tree)
                );
            }

            if (!$this->objectRepository->exists($objectHash)) {
                throw new InvalidObjectException(
                    sprintf('fatal: %s is not a valid object', $request->tree)
                );
            }

            $gitObject = $this->objectRepository->get($objectHash);
            if ($gitObject->objectType !== ObjectType::Tree) {
                throw new InvalidObjectTypeException(
                    sprintf('fatal: %s is not a valid \'tree\' object', $request->tree)
                );
            }

            $commitHash = $this->createCommitTree($objectHash, $request->message);

            $this->io->writeln($commitHash->value);

            return Result::Success;
        } catch (
            InvalidObjectNameException
            | InvalidObjectException
            | InvalidObjectTypeException $ex
        ) {
            $this->io->writeln($ex->getMessage());

            return Result::GitError;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::InternalError;
        }
    }

    private function createCommitTree(ObjectHash $objectHash, string $message): ObjectHash
    {
        $gitConfig = $this->gitConfigRepository->get();

        $timestamp = Timestamp::new();
        $author = GitSignature::new($gitConfig->userName, $gitConfig->userEmail, $timestamp);
        $committer = GitSignature::new($gitConfig->userName, $gitConfig->userEmail, $timestamp);
        $commit = CommitObject::new($objectHash, $author, $committer, $message);

        return $this->objectRepository->save($commit);
    }
}
