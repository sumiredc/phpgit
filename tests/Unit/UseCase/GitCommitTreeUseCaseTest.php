<?php

declare(strict_types=1);

use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\GitConfigRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Lib\IOInterface;
use Phpgit\Request\GitCommitTreeRequest;
use Phpgit\UseCase\GitCommitTreeUseCase;
use Symfony\Component\Console\Input\InputInterface;
use Tests\Factory\BlobObjectFactory;
use Tests\Factory\CommitObjectFactory;
use Tests\Factory\GitConfigFactory;
use Tests\Factory\TreeObjectFactory;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
    $this->io = Mockery::mock(IOInterface::class);
    $this->gitConfigRepository = Mockery::mock(GitConfigRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'outputs commit hash and returns to success',
        function (string $tree, string $message, ObjectHash $commitHash, string $expected) {
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn($tree);
            $this->input->shouldReceive('getOption')->with('message')->andReturn($message);

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('get')->andReturn(TreeObjectFactory::new())->once();
            $this->gitConfigRepository->shouldReceive('get')->andReturn(GitConfigFactory::new())->once();
            $this->objectRepository->shouldReceive('save')->andReturn($commitHash)->once();
            $this->io->shouldReceive('writeln')->with($expected)->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->io,
                $this->gitConfigRepository,
                $this->objectRepository,
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'tree' => '04ba9ed331f1eaa7618aefb1db4da5988463404d',
                'message' => 'dummy message',
                'commitHash' => ObjectHash::parse('ff9c936e7aafc01f64e60ae2fe5c79b229953d07'),
                'expected' => 'ff9c936e7aafc01f64e60ae2fe5c79b229953d07'
            ]
        ]);

    it(
        'throws an exception and outputs fatal message and returns error, when given to anything other string than a hash string',
        function (string $tree, string $expected) {
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn($tree);
            $this->input->shouldReceive('getOption')->with('message')->andReturn('dummy message');

            $this->io->shouldReceive('writeln')->with($expected)->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->io,
                $this->gitConfigRepository,
                $this->objectRepository,
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'not-hash',
                'fatal: not a valid object name not-hash'
            ]
        ]);

    it(
        'throws an exception and outputs fatal message and returns error, when does not get object',
        function (string $tree, string $expected) {
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn($tree);
            $this->input->shouldReceive('getOption')->with('message')->andReturn('dummy message');

            $this->objectRepository->shouldReceive('exists')->andReturn(false)->once();
            $this->io->shouldReceive('writeln')->with($expected)->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->io,
                $this->gitConfigRepository,
                $this->objectRepository,
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                '7325eb186677325ce158c51f203f3d026e48803b',
                'fatal: 7325eb186677325ce158c51f203f3d026e48803b is not a valid object'
            ]
        ]);

    it(
        'throws an exception and outputs fatal message and returns error, when does not match to tree object',
        function (string $tree, GitObject $gitObject, string $expected) {
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn($tree);
            $this->input->shouldReceive('getOption')->with('message')->andReturn('dummy message');

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('get')->andReturn($gitObject)->once();
            $this->io->shouldReceive('writeln')->with($expected)->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->io,
                $this->gitConfigRepository,
                $this->objectRepository,
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with(
            [
                'blob object' => [
                    '815675cd53e5196255182a0fd392e03df0fcd193',
                    BlobObjectFactory::new(),
                    'fatal: 815675cd53e5196255182a0fd392e03df0fcd193 is not a valid \'tree\' object'
                ],
                'commit object' => [
                    '0f423768892497ee49fd3cb7600685fc4d09048c',
                    CommitObjectFactory::new(),
                    'fatal: 0f423768892497ee49fd3cb7600685fc4d09048c is not a valid \'tree\' object'
                ],
                // TODO: tag object
            ]
        );

    it(
        'throws an exception and outputs stack trace and returns error, when happened unexpected error',
        function (Throwable $th, Throwable $expected) {
            $this->input->shouldReceive('getArgument')->with('tree')
                ->andReturn('04ba9ed331f1eaa7618aefb1db4da5988463404d');
            $this->input->shouldReceive('getOption')->with('message')->andReturn('dummy message');

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('get')->andThrow($th)->once();
            $this->io->shouldReceive('stackTrace')
                ->withArgs(function (Throwable $actual) use ($expected) {
                    expect($actual)->toEqual($expected);

                    return true;
                })
                ->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->io,
                $this->gitConfigRepository,
                $this->objectRepository,
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::InternalError);
        }
    )
        ->with([
            [
                new RuntimeException('dummy exception'),
                new RuntimeException('dummy exception'),
            ],
        ]);
});
