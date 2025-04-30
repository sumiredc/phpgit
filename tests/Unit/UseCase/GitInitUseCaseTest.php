<?php

declare(strict_types=1);

use Phpgit\Domain\Repository\GitResourceRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Lib\IOInterface;
use Phpgit\UseCase\GitInitUseCase;

beforeEach(function () {
    $this->io = Mockery::mock(IOInterface::class);
    $this->gitResourceRepository = Mockery::mock(GitResourceRepositoryInterface::class);
});

describe('__invoke', function () {
    it('should returns success code when initializes', function () {
        $this->gitResourceRepository->shouldReceive('existsGitDir')->andReturn(false)->once();
        $this->gitResourceRepository->shouldReceive('makeGitObjectDir')->once();
        $this->gitResourceRepository->shouldReceive('makeGitHeadsDir')->once();
        $this->gitResourceRepository->shouldReceive('createGitHead')->once();
        $this->gitResourceRepository->shouldReceive('saveGitHead')->once();
        $this->gitResourceRepository->shouldReceive('createConfig')->once();
        $this->io->shouldReceive('writeln')->once();

        $useCase = new GitInitUseCase($this->io, $this->gitResourceRepository);
        $actual = $useCase();

        expect($actual)->toBe(Result::Success);
    });

    it('should returns success code when reinitializes', function () {
        $this->gitResourceRepository->shouldReceive('existsGitDir')->andReturn(true)->once();
        $this->gitResourceRepository->shouldReceive('makeGitObjectDir')->never();
        $this->io->shouldReceive('writeln')->once();

        $useCase = new GitInitUseCase($this->io, $this->gitResourceRepository);
        $actual = $useCase();

        expect($actual)->toBe(Result::Success);
    });

    it('should calls io::stackTrace and returns git error, when throws Exception', function () {
        $this->gitResourceRepository->shouldReceive('existsGitDir')->andReturn(false)->once();
        $this->gitResourceRepository->shouldReceive('makeGitObjectDir')->andThrows(RuntimeException::class);
        $this->io->shouldReceive('stackTrace')->once();

        $useCase = new GitInitUseCase($this->io, $this->gitResourceRepository);
        $actual = $useCase();

        expect($actual)->toBe(Result::GitError);
    });
});
