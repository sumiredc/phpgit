<?php

declare(strict_types=1);

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\RevParseRequest;
use Phpgit\UseCase\RevParseUseCase;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
    $this->refRepository = Mockery::mock(RefRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'returns an success and outputs result of rev parse of HEAD in hash',
        function (array $args, ObjectHash $hash, array $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository->shouldReceive('resolveHead')->andReturn($hash)->once();
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expected))->once();

            $request = RevParseRequest::new($this->input);
            $useCase = new RevParseUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->refRepository
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            'args is one HEAD' => [
                'args' => ['HEAD'],
                'hash' => ObjectHash::parse('829c3804401b0727f70f73d4415e162400cbe57b'),
                'expected' => ['829c3804401b0727f70f73d4415e162400cbe57b']
            ],
        ]);

    it(
        'returns an success and outputs result of rev parse of reference',
        function (array $args, array $refs, array $objects, int $times, array $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository->shouldReceive('resolveHead')->never();
            foreach ($refs as $i => $ref) {
                $this->refRepository
                    ->shouldReceive('exists')->withArgs(expectEqualArg($ref))->andReturn(true)->once()
                    ->shouldReceive('resolve')->withArgs(expectEqualArg($ref))->andReturn($objects[$i])->once();
            }
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expected))->once();

            $request = RevParseRequest::new($this->input);
            $useCase = new RevParseUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->refRepository
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            'args is empty' => [
                'args' => [],
                'objects' => [],
                'refs' => [],
                'times' => 0,
                'expected' => []
            ],
            'args is one' => [
                'args' => ['refs/heads/main'],
                'objects' => [ObjectHash::parse('829c3804401b0727f70f73d4415e162400cbe57b')],
                'refs' => [Reference::parse('refs/heads/main')],
                'times' => 1,
                'expected' => ['829c3804401b0727f70f73d4415e162400cbe57b']
            ],
            'args is multi' => [
                'args' => [
                    'refs/heads/main',
                    'refs/heads/develop',
                    'refs/heads/stg',
                    'refs/heads/local',
                ],
                'refs' => [
                    Reference::parse('refs/heads/main'),
                    Reference::parse('refs/heads/develop'),
                    Reference::parse('refs/heads/stg'),
                    Reference::parse('refs/heads/local'),
                ],
                'objects' => [
                    ObjectHash::parse('b28b7af69320201d1cf206ebf28373980add1451'),
                    ObjectHash::parse('7a9a623862c43795ad7baf6afea3bdf868412e50'),
                    ObjectHash::parse('418a6bc4deccf0f7d5182192d51a54e504b3f3c9'),
                    ObjectHash::parse('939bb46a04c3640c8c427e92b1b557e882e2d2a0'),
                ],
                'times' => 4,
                'expected' => [
                    'b28b7af69320201d1cf206ebf28373980add1451',
                    '7a9a623862c43795ad7baf6afea3bdf868412e50',
                    '418a6bc4deccf0f7d5182192d51a54e504b3f3c9',
                    '939bb46a04c3640c8c427e92b1b557e882e2d2a0',
                ]
            ],
        ]);

    it(
        'returns an success and outputs result of rev parse of object',
        function (array $args, int $times, array $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository
                ->shouldReceive('resolveHead')->never()
                ->shouldReceive('exists')->never();
            $this->objectRepository->shouldReceive('exists')->andReturn(true)->times($times);
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expected))->once();

            $request = RevParseRequest::new($this->input);
            $useCase = new RevParseUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->refRepository
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            'args is one' => [
                'args' => ['829c3804401b0727f70f73d4415e162400cbe57b'],
                'times' => 1,
                'expected' => ['829c3804401b0727f70f73d4415e162400cbe57b']
            ],
            'args is multi' => [
                'args' => [
                    'b28b7af69320201d1cf206ebf28373980add1451',
                    '7a9a623862c43795ad7baf6afea3bdf868412e50',
                    '418a6bc4deccf0f7d5182192d51a54e504b3f3c9',
                    '939bb46a04c3640c8c427e92b1b557e882e2d2a0',
                ],
                'times' => 4,
                'expected' => [
                    'b28b7af69320201d1cf206ebf28373980add1451',
                    '7a9a623862c43795ad7baf6afea3bdf868412e50',
                    '418a6bc4deccf0f7d5182192d51a54e504b3f3c9',
                    '939bb46a04c3640c8c427e92b1b557e882e2d2a0',
                ]
            ],
        ]);

    it(
        'returns an success and outputs result of rev parse of file',
        function (array $args, int $times, array $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository
                ->shouldReceive('resolveHead')->never()
                ->shouldReceive('exists')->never();
            $this->objectRepository->shouldReceive('exists')->never();
            foreach ($args as $arg) {
                $this->fileRepository->shouldReceive('existsByFilename')->with($arg)->andReturn(true)->once();
            }
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expected))->once();
            $request = RevParseRequest::new($this->input);
            $useCase = new RevParseUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->refRepository
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            'args is one' => [
                'args' => ['README.md'],
                'times' => 1,
                'expected' => ['README.md'],
            ],
            'args is multi' => [
                'args' => [
                    'README.md',
                    'go/main.go',
                    'rust/main.rs',
                    'php/index.php',
                ],
                'times' => 4,
                'expected' => [
                    'README.md',
                    'go/main.go',
                    'rust/main.rs',
                    'php/index.php',
                ]
            ],
        ]);

    it(
        'returns an error and outputs fatal message unknown revesion or path on throws the RevisionNotFoundException',
        function (array $args, array $expectedResults, string $expectedMessage) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->fileRepository->shouldReceive('existsByFilename')->andReturn(false)->once();
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expectedResults))->once();
            $this->printer->shouldReceive('writeln')->with($expectedMessage)->once();

            $request = RevParseRequest::new($this->input);
            $useCase = new RevParseUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->refRepository
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'args' => ['DONT_EXISTS.md'],
                'expectedResults' => [],
                'expectedMessage' => 'fatal: ambiguous argument \'DONT_EXISTS.md\': unknown revision or path not in the working tree.'
            ]
        ]);

    it(
        'returns an error and outputs fatal message unknown revesion or path on throws the RevisionNotFoundException when does not exists reference',
        function (array $args, Reference $ref, array $expectedResults, string $expectedMessage) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository->shouldReceive('exists')->withArgs(expectEqualArg($ref))->andReturn(false)->once();
            $this->fileRepository->shouldReceive('existsByFilename')->andReturn(false)->once();
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expectedResults))->once();
            $this->printer->shouldReceive('writeln')->with($expectedMessage)->once();

            $request = RevParseRequest::new($this->input);
            $useCase = new RevParseUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->refRepository
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'args' => ['refs/heads/dont-exists'],
                'ref' => Reference::parse('refs/heads/dont-exists'),
                'expectedResults' => [],
                'expectedMessage' => 'fatal: ambiguous argument \'refs/heads/dont-exists\': unknown revision or path not in the working tree.'
            ]
        ]);

    it(
        'outputs success results until throws an error',
        function (
            array $args,
            array $filenameExists,
            array $expectedResults,
            string $expectedMessage
        ) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            foreach ($filenameExists as $filename => $exists) {
                $this->fileRepository->shouldReceive('existsByFilename')->with($filename)->andReturn($exists);
            }
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expectedResults))->once();
            $this->printer->shouldReceive('writeln')->with($expectedMessage)->once();

            $request = RevParseRequest::new($this->input);
            $useCase = new RevParseUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->refRepository
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'args' => [
                    'README.md',
                    'go/main.go',
                    'DONT_EXISTS.md',
                    'rust/main.rs',
                    'php/index.php',
                ],
                'filenameExists' => [
                    'README.md' => true,
                    'go/main.go' => true,
                    'DONT_EXISTS.md' => false,
                    'rust/main.rs' => true,
                    'php/index.php' => true,
                ],
                'expectedResults' => [
                    'README.md',
                    'go/main.go',
                ],
                'expectedMessage' => 'fatal: ambiguous argument \'DONT_EXISTS.md\': unknown revision or path not in the working tree.'
            ]
        ]);;

    it(
        'returns an internal error and outputs stack trace on throws an exceptions',
        function (array $args, Throwable $exception, Throwable $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository->shouldReceive('resolveHead')->andThrow($exception)->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = RevParseRequest::new($this->input);
            $useCase = new RevParseUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->refRepository
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::InternalError);
        }
    )
        ->with([
            [
                'args' => ['HEAD'],
                'exception' => new RuntimeException('failed to resolve of HEAD'),
                'expected' => new RuntimeException('failed to resolve of HEAD'),
            ]
        ]);
});
