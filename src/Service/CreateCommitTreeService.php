<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\CommitObject;
use Phpgit\Domain\GitSignature;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\GitConfigRepositoryInterface;
use Phpgit\Domain\Timestamp;

readonly final class CreateCommitTreeService implements CreateCommitTreeServiceInterface
{
    public function __construct(
        private readonly GitConfigRepositoryInterface $gitConfigRepository
    ) {}

    public function __invoke(ObjectHash $treetHash, string $message, ?ObjectHash $parentHash): CommitObject
    {
        $gitConfig = $this->gitConfigRepository->get();

        $timestamp = Timestamp::new();
        $author = GitSignature::new($gitConfig->userName, $gitConfig->userEmail, $timestamp);
        $committer = GitSignature::new($gitConfig->userName, $gitConfig->userEmail, $timestamp);

        return CommitObject::new($treetHash, $author, $committer, $message, $parentHash);
    }
}
