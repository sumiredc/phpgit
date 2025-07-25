<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\CatFileRequest;
use Phpgit\UseCase\CatFileUseCase;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $command = Mockery::mock(CommandInterface::class);
    $command->shouldReceive('addArgument')->shouldReceive('addOption');
    CatFileRequest::setUp($command);

    $this->input = Mockery::mock(InputInterface::class);
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'should returns error, then throws InvalidArgumentException by fails to parse hash',
        function (string $object, string $expected) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(true);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->never();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'not-hash-pattern1',
                'fatal: Not a valid object name not-hash-pattern1'
            ],
            [
                'not-hash-pattern2',
                'fatal: Not a valid object name not-hash-pattern2'
            ]
        ]);
});

describe('__invoke -> actionType', function () {
    it(
        'should returns success and output object type',
        function (string $object, string $blob, string $expected) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('get')->andReturn(GitObject::parse($blob))->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'd0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33',
                "blob 38\0fn main() { println!(\"Hello world\"); }",
                'blob'
            ],
            [
                '4b825dc642cb6eb9a060e54bf8d69288fbee4904',
                "tree 0\0",
                'tree'
            ]
        ]);

    it(
        'should returns error, then throws CannotGetObjectInfoException',
        function (string $object) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->andReturn(false)->once();
            $this->objectRepository->shouldReceive('get')->never();
            $this->printer->shouldReceive('writeln')->with('fatal: git cat-file: could not get object info')->once();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            ['d0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33']
        ]);
});

describe('__invoke -> actionSize', function () {
    it(
        'should returns success and output object size',
        function (string $object, string $blob, string $expected) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('get')->andReturn(GitObject::parse($blob))->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'd0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33',
                "blob 38\0fn main() { println!(\"Hello world\"); }",
                '38'
            ],
            [
                '4b825dc642cb6eb9a060e54bf8d69288fbee4904',
                "tree 0\0",
                '0'
            ]
        ]);

    it(
        'should returns error, then throws CannotGetObjectInfoException',
        function (string $object) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->andReturn(false)->once();
            $this->objectRepository->shouldReceive('get')->never();
            $this->printer->shouldReceive('writeln')->with('fatal: git cat-file: could not get object info')->once();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            ['d0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33']
        ]);
});

describe('__invoke -> actionExists', function () {
    it(
        'should returns success when exists object',
        function (string $object) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->printer->shouldReceive('writeln')->never();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['4b825dc642cb6eb9a060e54bf8d69288fbee4904']
        ]);

    it(
        'should returns failure when not exists object',
        function (string $object) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->andReturn(false)->once();
            $this->printer->shouldReceive('writeln')->never();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Failure);
        }
    )
        ->with([
            ['d0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33']
        ]);
});

describe('__invoke -> actionPrettyPrint', function () {
    it(
        'should returns success when output object info',
        function (string $object, string $blob, string $expected) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(true);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('get')->andReturn(GitObject::parse($blob))->once();
            $this->printer->shouldReceive('write')->with($expected)->once();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'd0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33',
                "blob 38\0fn main() { println!(\"Hello world\"); }",
                'fn main() { println!("Hello world"); }'
            ],
            [
                '4b825dc642cb6eb9a060e54bf8d69288fbee4904',
                "tree 0\0",
                ''
            ]
        ]);

    it(
        'should returns error, then throws InvalidArgumentException',
        function (string $object, string $expected) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(true);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->andReturn(false)->once();
            $this->objectRepository->shouldReceive('get')->never();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'd0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33',
                'fatal: Not a valid object name d0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33'
            ],
            [
                '4b825dc642cb6eb9a060e54bf8d69288fbee4904',
                'fatal: Not a valid object name 4b825dc642cb6eb9a060e54bf8d69288fbee4904'
            ]
        ]);

    it(
        'should returns error, then throws RuntimeException',
        function (string $object, Throwable $expected) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(true);
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object);

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('get')->andThrow(RuntimeException::class);
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = CatFileRequest::new($this->input);
            $useCase = new CatFileUseCase($this->printer, $this->objectRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::InternalError);
        }
    )
        ->with([
            ['d0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33', new RuntimeException],
        ]);
});
