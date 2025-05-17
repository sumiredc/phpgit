<?php

declare(strict_types=1);

use Phpgit\Domain\Repository\GitConfigRepositoryInterface;
use Phpgit\Domain\Repository\GitResourceRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\UseCase\InitUseCase;

beforeEach(function () {
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->gitResourceRepository = Mockery::mock(GitResourceRepositoryInterface::class);
    $this->gitConfigRepository = Mockery::mock(GitConfigRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'should returns success code when initializes',
        function () {
            $this->gitResourceRepository->shouldReceive('existsGitDir')->andReturn(false)->once();
            $this->gitResourceRepository->shouldReceive('makeGitObjectDir')->once();
            $this->gitResourceRepository->shouldReceive('makeGitHeadsDir')->once();
            $this->gitResourceRepository->shouldReceive('createGitHead')->once();
            $this->gitResourceRepository->shouldReceive('saveGitHead')->once();
            $this->gitConfigRepository->shouldReceive('create')->once();
            $this->printer->shouldReceive('writeln')->once();

            $useCase = new InitUseCase($this->printer, $this->gitResourceRepository, $this->gitConfigRepository);
            $actual = $useCase();

            expect($actual)->toBe(Result::Success);
        }
    );

    it(
        'should returns success code when reinitializes',
        function () {
            $this->gitResourceRepository->shouldReceive('existsGitDir')->andReturn(true)->once();
            $this->gitResourceRepository->shouldReceive('makeGitObjectDir')->never();
            $this->printer->shouldReceive('writeln')->once();

            $useCase = new InitUseCase($this->printer, $this->gitResourceRepository, $this->gitConfigRepository);
            $actual = $useCase();

            expect($actual)->toBe(Result::Success);
        }
    );

    it(
        'should calls io::stackTrace and returns git error, when throws Exception',
        function () {
            $this->gitResourceRepository->shouldReceive('existsGitDir')->andReturn(false)->once();
            $this->gitResourceRepository->shouldReceive('makeGitObjectDir')->andThrows(RuntimeException::class);
            $this->printer->shouldReceive('stackTrace')->once();

            $useCase = new InitUseCase($this->printer, $this->gitResourceRepository, $this->gitConfigRepository);
            $actual = $useCase();

            expect($actual)->toBe(Result::InternalError);
        }
    );
});
