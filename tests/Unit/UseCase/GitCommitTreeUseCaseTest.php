<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\GitConfigRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\GitCommitTreeRequest;
use Phpgit\UseCase\GitCommitTreeUseCase;
use Symfony\Component\Console\Input\InputInterface;
use Tests\Factory\BlobObjectFactory;
use Tests\Factory\CommitObjectFactory;
use Tests\Factory\GitConfigFactory;
use Tests\Factory\TreeObjectFactory;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->gitConfigRepository = Mockery::mock(GitConfigRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);

    $command = Mockery::mock(CommandInterface::class);
    $command->shouldReceive(['addOption' => $command, 'addArgument' => $command]);
    GitCommitTreeRequest::setUp($command);
});

describe('__invoke', function () {
    it(
        'outputs commit hash and returns to success',
        function (
            string $tree,
            string $message,
            ?string $parent,
            int $objectExistsCallCount,
            ObjectHash $commitHash,
            string $expected
        ) {
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn($tree)
                ->shouldReceive('getOption')->with('message')->andReturn($message)
                ->shouldReceive('getOption')->with('parent')->andReturn($parent);

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->times($objectExistsCallCount);
            $this->objectRepository->shouldReceive('get')->andReturn(TreeObjectFactory::new())->once();
            $this->gitConfigRepository->shouldReceive('get')->andReturn(GitConfigFactory::new())->once();
            $this->objectRepository->shouldReceive('save')->andReturn($commitHash)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->printer,
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
                'parent' => null,
                'objectExistsCallCount' => 1,
                'commitHash' => ObjectHash::parse('ff9c936e7aafc01f64e60ae2fe5c79b229953d07'),
                'expected' => 'ff9c936e7aafc01f64e60ae2fe5c79b229953d07'
            ],
            [
                'tree' => '04ba9ed331f1eaa7618aefb1db4da5988463404d',
                'message' => 'dummy message',
                'parent' => '0dbfdcf3970a6ea0761850575dcf5458451c7cde',
                'objectExistsCallCount' => 2,
                'commitHash' => ObjectHash::parse('ff9c936e7aafc01f64e60ae2fe5c79b229953d07'),
                'expected' => 'ff9c936e7aafc01f64e60ae2fe5c79b229953d07'
            ]
        ]);

    it(
        'throws an exception and outputs fatal message and returns error, when given to anything other string than a hash string',
        function (string $tree, ?string $parent, string $expected) {
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn($tree)
                ->shouldReceive('getOption')->with('message')->andReturn('dummy message')
                ->shouldReceive('getOption')->with('parent')->andReturn($parent);

            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->printer,
                $this->gitConfigRepository,
                $this->objectRepository,
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            'invalid tree object' => [
                'not-hash',
                null,
                'fatal: not a valid object name not-hash'
            ],
            'invalid parent object' => [
                'ff9c936e7aafc01f64e60ae2fe5c79b229953d07',
                'fail-hash',
                'fatal: not a valid object name fail-hash'
            ]
        ]);

    it(
        'throws an exception and outputs fatal message and returns error, when does not exists object',
        function (
            string $tree,
            ?string $parent,
            array $objectExistsReturns,
            int $objectExistsCallCount,
            string $expected
        ) {
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn($tree)
                ->shouldReceive('getOption')->with('message')->andReturn('dummy message')
                ->shouldReceive('getOption')->with('parent')->andReturn($parent);

            $this->objectRepository->shouldReceive('exists')->andReturn(...$objectExistsReturns)->times($objectExistsCallCount);
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->printer,
                $this->gitConfigRepository,
                $this->objectRepository,
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            'does not exists tree object' => [
                'tree' => '7325eb186677325ce158c51f203f3d026e48803b',
                'parent' => null,
                'objectExistsReturns' => [false],
                'objectExistsCallCount' => 1,
                'expected' => 'fatal: 7325eb186677325ce158c51f203f3d026e48803b is not a valid object'
            ],
            'does not exists parent object' => [
                'tree' => '7325eb186677325ce158c51f203f3d026e48803b',
                'parent' => 'ff9c936e7aafc01f64e60ae2fe5c79b229953d07',
                'objectExistsReturns' => [true, false],
                'objectExistsCallCount' => 2,
                'expected' => 'fatal: ff9c936e7aafc01f64e60ae2fe5c79b229953d07 is not a valid object'
            ],
        ]);

    it(
        'throws an exception and outputs fatal message and returns error, when does not match to tree object',
        function (string $tree, GitObject $gitObject, string $expected) {
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn($tree)
                ->shouldReceive('getOption')->with('message')->andReturn('dummy message')
                ->shouldReceive('getOption')->with('parent')->andReturnNull();

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('get')->andReturn($gitObject)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->printer,
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
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn('04ba9ed331f1eaa7618aefb1db4da5988463404d')
                ->shouldReceive('getOption')->with('message')->andReturn('dummy message')
                ->shouldReceive('getOption')->with('parent')->andReturnNull();

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('get')->andThrow($th)->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = GitCommitTreeRequest::new($this->input);
            $useCase = new GitCommitTreeUseCase(
                $this->printer,
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
