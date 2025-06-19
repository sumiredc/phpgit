<?php

declare(strict_types=1);

use Phpgit\Domain\CommitObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\GitConfigRepositoryInterface;
use Phpgit\Service\CreateCommitTreeService;
use Tests\Factory\GitConfigFactory;
use Tests\Factory\GitSignatureFactory;

beforeEach(function () {
    $this->gitConfigRepository = Mockery::mock(GitConfigRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'returns commit object includes given tree hash',
        function (
            ObjectHash $treeHash,
            string $message,
            ?ObjectHash $parentHash,
            CommitObject $expected,
        ) {
            $this->gitConfigRepository
                ->shouldReceive('get')->andReturn(GitConfigFactory::new())->once();

            $service = new CreateCommitTreeService($this->gitConfigRepository);
            $actual = $service($treeHash, $message, $parentHash);

            expect($actual->treeHash())->toEqual($expected->treeHash());
        }
    )
        ->with([
            fn() => [
                'treeHash' => ObjectHash::parse('80655da8d80aaaf92ce5357e7828dc09adb00993'),
                'message' => 'first commit',
                'parentHash' => null,
                'expected' => CommitObject::new(
                    ObjectHash::parse('80655da8d80aaaf92ce5357e7828dc09adb00993'),
                    GitSignatureFactory::new(),
                    GitSignatureFactory::new(),
                    'first commit',
                    null,
                )
            ],
            fn() => [
                'treeHash' => ObjectHash::parse('fc01489d8afd08431c7245b4216ea9d01856c3b9'),
                'message' => 'second commit',
                'parentHash' => ObjectHash::parse('80655da8d80aaaf92ce5357e7828dc09adb00993'),
                'expected' => CommitObject::new(
                    ObjectHash::parse('fc01489d8afd08431c7245b4216ea9d01856c3b9'),
                    GitSignatureFactory::new(),
                    GitSignatureFactory::new(),
                    'second commit',
                    ObjectHash::parse('80655da8d80aaaf92ce5357e7828dc09adb00993'),
                )
            ],
        ]);
});
