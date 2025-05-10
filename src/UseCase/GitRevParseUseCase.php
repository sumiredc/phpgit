<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\RevisionNotFoundException;
use Phpgit\Lib\IOInterface;
use Phpgit\Request\GitRevParseRequest;
use Throwable;

final class GitRevParseUseCase
{
    public function __construct(
        private readonly IOInterface $io,
        private readonly FileRepositoryInterface $fileRepository,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly RefRepositoryInterface $refRepository,
    ) {}

    public function __invoke(GitRevParseRequest $request): Result
    {
        $results = [];

        try {
            foreach ($request->args as $arg) {
                [$result, $ex] = $this->parse($arg);

                if (!is_null($ex)) {
                    throw $ex;
                }

                $results[] = $result;
            }

            $this->io->writeln($results);

            return Result::Success;
        } catch (RevisionNotFoundException $revEx) {
            $this->io->writeln($results);
            $this->io->writeln($revEx->getMessage());

            return Result::GitError;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::InternalError;
        }
    }

    /** @return array{0:string,1:?Throwable} */
    private function parse(string $arg): array
    {
        $headCommitHash = $this->parseHead($arg);
        if (!is_null($headCommitHash)) {
            return [$headCommitHash->value, null];
        }

        $branchCommitHash = $this->parseRef($arg);
        if ($branchCommitHash) {
            return [$branchCommitHash->value, null];
        }

        $isObject = $this->parseObject($arg);
        if ($isObject) {
            return [$arg, null];
        }

        $isFile = $this->parseFile($arg);
        if ($isFile) {
            return [$arg, null];
        }

        return [
            $arg,
            new RevisionNotFoundException(
                sprintf('fatal: ambiguous argument \'%s\': unknown revision or path not in the working tree.', $arg)
            )
        ];
    }

    private function parseHead(string $arg): ?ObjectHash
    {
        if ($arg !== GIT_HEAD) {
            return null;
        }

        return $this->refRepository->resolveHead();
    }

    private function parseRef(string $arg): ?ObjectHash
    {
        $ref = Reference::tryParse($arg);
        if (is_null($ref)) {
            return null;
        }

        if (!$this->refRepository->exists($ref)) {
            return null;
        }

        return $this->refRepository->resolve($ref);
    }

    private function parseObject(string $arg): bool
    {
        $objectHash = ObjectHash::tryParse($arg);
        if (is_null($objectHash)) {
            return false;
        }

        return $this->objectRepository->exists($objectHash);
    }

    private function parseFile(string $arg): bool
    {
        return $this->fileRepository->existsByFilename($arg);
    }
}
