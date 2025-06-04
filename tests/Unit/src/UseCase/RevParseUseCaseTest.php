<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\HeadType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\TrackedPath;
use Phpgit\Request\RevParseRequest;
use Phpgit\UseCase\RevParseUseCase;
use Symfony\Component\Console\Input\InputInterface;
use Tests\Factory\ReferenceFactory;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
    $this->refRepository = Mockery::mock(RefRepositoryInterface::class);

    $command = Mockery::mock(CommandInterface::class);
    $command->shouldReceive('addArgument');
    RevParseRequest::setUp($command);
});

describe('__invoke, parse head', function () {
    it(
        'returns a success and outputs result of rev parse of HEAD, on it is written a reference',
        function (array $args, ObjectHash $hash, array $expected) {
            $ref = ReferenceFactory::local();

            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturn($ref)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($ref))->andReturn(true)
                ->shouldReceive('resolve')->withArgs(expectEqualArg($ref))->andReturn($hash)->once();
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
        'returns a success and outputs result of rev parse of HEAD, on it is written a hash',
        function (array $args, ObjectHash $hash, array $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Hash)->once()
                ->shouldReceive('resolveHead')->andReturn($hash)->once();
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
        'returns an internal error and outputs stack trace on head type is unknown',
        function (array $args, HeadType $headType, Throwable $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository->shouldReceive('headType')->andReturn($headType)->once();
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
                'headType' => HeadType::Unknown,
                'expected' => new LogicException('HEAD is Unknown'),
            ]
        ]);

    it(
        'returns an internal error and outputs stack trace on head does not resolved',
        function (array $args, Throwable $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturnNull()->once();
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
                'expected' => new LogicException('cannot resolved HEAD'),
            ]
        ]);

    it(
        'returns an error and outputs fatal message, on does not exists ref in HEAD',
        function (array $args, string $expected) {
            $ref = ReferenceFactory::local();

            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturn($ref)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($ref))->andReturn(false);
            $this->printer
                ->shouldReceive('writeln')->with([])->once()
                ->shouldReceive('writeln')->with($expected)->once();

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
                'args' => ['HEAD'],
                'expected' => 'fatal: ambiguous argument \'HEAD\': unknown revision or path not in the working tree.',
            ]
        ]);
});

describe('__invoke, parse ref', function () {
    it(
        'returns a success and outputs result of rev parse of reference',
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
        'returns an error and outputs fatal message unknown revesion or path on does not exists revision when does not exists reference',
        function (array $args, Reference $ref, array $expectedResults, string $expectedMessage) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository->shouldReceive('exists')->withArgs(expectEqualArg($ref))->andReturn(false)->once();
            $this->fileRepository->shouldReceive('exists')->andReturn(false)->once();
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
});

describe('__invoke, parse object', function () {
    it(
        'returns a success and outputs result of rev parse of object',
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
});

describe('__invoke, parse file', function () {
    it(
        'returns a success and outputs result of rev parse of file',
        function (array $args, array $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->refRepository
                ->shouldReceive('resolveHead')->never()
                ->shouldReceive('exists')->never();
            $this->objectRepository->shouldReceive('exists')->never();
            foreach ($args as $arg) {
                $this->fileRepository->shouldReceive('exists')
                    ->withArgs(expectEqualArg(TrackedPath::parse($arg)))
                    ->andReturn(true)
                    ->once();
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
                'expected' => ['README.md'],
            ],
            'args is multi' => [
                'args' => [
                    'README.md',
                    'go/main.go',
                    'rust/main.rs',
                    'php/index.php',
                ],
                'expected' => [
                    'README.md',
                    'go/main.go',
                    'rust/main.rs',
                    'php/index.php',
                ]
            ],
        ]);
});

describe('__invoke, other cases', function () {
    it(
        'returns an error and outputs fatal message unknown revesion or path on does not exists revision',
        function (array $args, array $expectedResults, string $expectedMessage) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->fileRepository->shouldReceive('exists')->andReturn(false)->once();
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
        'outputs success results until throws an error',
        function () {
            $args = [
                'README.md',
                'go/main.go',
                'DONT_EXISTS.md',
                'rust/main.rs',
                'php/index.php',
            ];

            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();
            $this->fileRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg(TrackedPath::parse('README.md')))->andReturn(true)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg(TrackedPath::parse('go/main.go')))->andReturn(true)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg(TrackedPath::parse('DONT_EXISTS.md')))->andReturn(false)->once();
            $this->printer
                ->shouldReceive('writeln')->withArgs(expectEqualArg(['README.md', 'go/main.go',]))->once()
                ->shouldReceive('writeln')->with('fatal: ambiguous argument \'DONT_EXISTS.md\': unknown revision or path not in the working tree.')->once();

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
    );
});
