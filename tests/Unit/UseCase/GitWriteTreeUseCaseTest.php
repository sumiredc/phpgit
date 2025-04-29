<?php

declare(strict_types=1);

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Lib\IOInterface;
use Phpgit\UseCase\GitWriteTreeUseCase;
use Tests\Factory\GitIndexFactory;
use Tests\Factory\IndexEntryFactory;

beforeEach(function () {
    $this->io = Mockery::mock(IOInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'should return success and output object hash',
        function (ObjectHash $objectHash, string $expected) {
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndexFactory::new())->once();
            $this->objectRepository->shouldReceive('save')->andReturn($objectHash); // in service
            $this->io->shouldReceive('writeln')->with($expected)->once();

            $useCase = new GitWriteTreeUseCase($this->io, $this->indexRepository, $this->objectRepository);
            $actual = $useCase();

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                ObjectHash::parse('829c3804401b0727f70f73d4415e162400cbe57b'),
                '829c3804401b0727f70f73d4415e162400cbe57b',
            ]
        ]);

    it(
        'should return error and output fatal message, when throws the InvalidObjectException',
        function (GitIndex $index, array $expected) {
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->objectRepository->shouldReceive('exists')->andReturn(false);

            $this->io->shouldReceive('writeln')
                ->withArgs(function (array $actual) use ($expected) {
                    expect($actual)->toEqual($expected);

                    return true;
                })
                ->once();

            $useCase = new GitWriteTreeUseCase($this->io, $this->indexRepository, $this->objectRepository);
            $actual = $useCase();

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            function () {
                $index = GitIndexFactory::new();
                $entry = IndexEntryFactory::new();
                $index->addEntry($entry);

                $expected = [
                    sprintf(
                        'error: invalid object %s %s for \'%s\'',
                        $entry->gitFileMode->value,
                        $entry->objectHash->value,
                        $entry->trackingFile->path
                    ),
                    'fatal: git-write-tree: error building trees'
                ];

                return [$index, $expected];
            }
        ]);

    it(
        'should return error and output stack trace, when throws the Exception',
        function (Throwable $expected) {
            $this->indexRepository->shouldReceive('getOrCreate')->andThrow($expected)->once();
            $this->objectRepository->shouldReceive('exists')->andReturn(false);

            $this->io->shouldReceive('stackTrace')
                ->withArgs(function (Throwable $actual) use ($expected) {
                    expect($actual)->toEqual($expected);

                    return true;
                })
                ->once();

            $useCase = new GitWriteTreeUseCase($this->io, $this->indexRepository, $this->objectRepository);
            $actual = $useCase();

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [new RuntimeException('dummy runtime exception')],
            [new DomainException('dummy domain exception')],
        ]);
});
