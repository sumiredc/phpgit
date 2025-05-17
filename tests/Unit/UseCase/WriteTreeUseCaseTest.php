<?php

declare(strict_types=1);

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\UseCase\WriteTreeUseCase;
use Tests\Factory\GitIndexFactory;
use Tests\Factory\IndexEntryFactory;

beforeEach(function () {
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'should return success and output object hash',
        function (ObjectHash $objectHash, string $expected) {
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndexFactory::new())->once();
            $this->objectRepository->shouldReceive('save')->andReturn($objectHash); // in service
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $useCase = new WriteTreeUseCase($this->printer, $this->indexRepository, $this->objectRepository);
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
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expected))->once();

            $useCase = new WriteTreeUseCase($this->printer, $this->indexRepository, $this->objectRepository);
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
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $useCase = new WriteTreeUseCase($this->printer, $this->indexRepository, $this->objectRepository);
            $actual = $useCase();

            expect($actual)->toBe(Result::InternalError);
        }
    )
        ->with([
            [new RuntimeException('dummy runtime exception')],
            [new DomainException('dummy domain exception')],
        ]);
});
