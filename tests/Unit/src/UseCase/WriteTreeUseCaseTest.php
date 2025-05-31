<?php

declare(strict_types=1);

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Exception\InvalidObjectException;
use Phpgit\Service\CreateSegmentTreeServiceInterface;
use Phpgit\Service\SaveTreeObjectServiceInterface;
use Phpgit\UseCase\WriteTreeUseCase;
use Tests\Factory\GitIndexFactory;
use Tests\Factory\SegmentTreeFactory;

beforeEach(function () {
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
    $this->createSegmentTreeService = Mockery::mock(CreateSegmentTreeServiceInterface::class);
    $this->saveTreeObjectService = Mockery::mock(SaveTreeObjectServiceInterface::class);
});

describe('__invoke', function () {
    it(
        'returns to success and outputs object hash',
        function (ObjectHash $objectHash, string $expected) {
            $gitIndex = GitIndexFactory::new();
            $segmentTree = SegmentTreeFactory::new();

            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($gitIndex)->once();
            $this->createSegmentTreeService->shouldReceive('__invoke')
                ->withArgs(expectEqualArg($gitIndex))->andReturn($segmentTree)->once();
            $this->saveTreeObjectService->shouldReceive('__invoke')
                ->withArgs(expectEqualArg($segmentTree))->andReturn($objectHash)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $useCase = new WriteTreeUseCase(
                $this->printer,
                $this->indexRepository,
                $this->createSegmentTreeService,
                $this->saveTreeObjectService,
            );
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
        'returns error and output fatal message, on throws the InvalidObjectException',
        function (Throwable $th, array $expected) {
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndexFactory::new())->once();
            $this->createSegmentTreeService->shouldReceive('__invoke')->andThrow($th)->once();
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expected))->once();

            $useCase = new WriteTreeUseCase(
                $this->printer,
                $this->indexRepository,
                $this->createSegmentTreeService,
                $this->saveTreeObjectService,
            );
            $actual = $useCase();

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                new InvalidObjectException('error: invalid object 100644 829c3804401b0727f70f73d4415e162400cbe57b for \'dummy/path\''),
                [
                    'error: invalid object 100644 829c3804401b0727f70f73d4415e162400cbe57b for \'dummy/path\'',
                    'fatal: git-write-tree: error building trees'
                ]
            ]
        ]);

    it(
        'should return error and output stack trace, when throws the Exception',
        function (Throwable $expected) {
            $this->indexRepository->shouldReceive('getOrCreate')->andThrow($expected)->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $useCase = new WriteTreeUseCase(
                $this->printer,
                $this->indexRepository,
                $this->createSegmentTreeService,
                $this->saveTreeObjectService,
            );
            $actual = $useCase();

            expect($actual)->toBe(Result::InternalError);
        }
    )
        ->with([
            [new RuntimeException('dummy runtime exception')],
            [new DomainException('dummy domain exception')],
        ]);
});
