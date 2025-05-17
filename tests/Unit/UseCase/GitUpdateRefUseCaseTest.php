<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\GitUpdateRefRequest;
use Phpgit\UseCase\GitUpdateRefUseCase;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
    $this->refRepository = Mockery::mock(RefRepositoryInterface::class);
    $this->input = Mockery::mock(InputInterface::class);

    $command = Mockery::mock(CommandInterface::class);
    $command->shouldReceive(['addOption' => $command, 'addArgument' => $command]);
    GitUpdateRefRequest::setUp($command);
});

describe('__invoke::actionUpdate', function () {
    it(
        'returns to success, dereference is returns null',
        function (string $ref, string $newValue) {
            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($newValue)
                ->shouldReceive('getArgument')->with('arg2')->andReturnNull();
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturnNull()->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['no-reference', '7138a51661947b19b5088da5a2bfede2876f49b9']
        ]);

    it(
        'returns to success and update reference',
        function (string $ref, string $newValue) {
            $reference = Reference::parse($ref);
            $newObject = ObjectHash::parse($newValue);

            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($newValue)
                ->shouldReceive('getArgument')->with('arg2')->andReturnNull();
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturn($reference)->once();
            $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg($newObject))->andReturn(true)->once();
            $this->refRepository->shouldReceive('createOrUpdate')->withArgs(expectEqualArg($reference, $newObject))->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['refs/heads/main', '7138a51661947b19b5088da5a2bfede2876f49b9']
        ]);

    it(
        'returns to success and strict update reference',
        function (string $ref, string $newValue, string $oldValue, string $currentValue) {
            $reference = Reference::parse($ref);
            $newObject = ObjectHash::parse($newValue);
            $oldObject = ObjectHash::parse($oldValue);
            $currentObject = ObjectHash::parse($currentValue);

            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($newValue)
                ->shouldReceive('getArgument')->with('arg2')->andReturn($oldValue);
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturn($reference)->once();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($newObject))->andReturn(true)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($oldObject))->andReturn(true)->once();
            $this->refRepository
                ->shouldReceive('resolve')->withArgs(expectEqualArg($reference))->andReturn($currentObject)->once()
                ->shouldReceive('update')->withArgs(expectEqualArg($reference, $newObject))->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'refs/heads/main',
                '2a5e2b259a289475d62313e89b4643b3e912301d',
                'f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
                'f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11'
            ]
        ]);

    it(
        'throws the UseCaseException and outputs fatal message, on newobject is not sha1',
        function (string $ref, string $newValue, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($newValue)
                ->shouldReceive('getArgument')->with('arg2')->andReturnNull();
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturn(Reference::parse($ref))->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'refs/heads/main',
                'not-sha1',
                'fatal: not-sha1: not a valid SHA1',
            ]
        ]);

    it(
        'throws the UseCaseException and outputs fatal message, on newobject does exists',
        function (string $ref, string $newValue, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($newValue)
                ->shouldReceive('getArgument')->with('arg2')->andReturnNull();
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturn(Reference::parse($ref))->once();
            $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg(ObjectHash::parse($newValue)))->andReturn(false)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'refs/heads/main',
                '2a5e2b259a289475d62313e89b4643b3e912301d',
                'fatal: update_ref failed for ref \'refs/heads/main\': cannot update ref \'refs/heads/main\': trying to write ref \'refs/heads/main\' with nonexistent object 2a5e2b259a289475d62313e89b4643b3e912301d'
            ]
        ]);

    it(
        'throws the UseCaseException and outputs fatal message, on oldobject is not sha1',
        function (string $ref, string $newValue, string $oldValue, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($newValue)
                ->shouldReceive('getArgument')->with('arg2')->andReturn($oldValue);
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturn(Reference::parse($ref))->once();
            $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg(ObjectHash::parse($newValue)))->andReturn(true)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'refs/heads/main',
                '2a5e2b259a289475d62313e89b4643b3e912301d',
                'not-sha1',
                'fatal: not-sha1: not a valid SHA1',
            ]
        ]);

    it(
        'throws the UseCaseException and outputs fatal message, on oldobject does exists',
        function (string $ref, string $newValue, string $oldValue, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($newValue)
                ->shouldReceive('getArgument')->with('arg2')->andReturn($oldValue);
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturn(Reference::parse($ref))->once();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg(ObjectHash::parse($newValue)))->andReturn(true)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg(ObjectHash::parse($oldValue)))->andReturn(false)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'refs/heads/main',
                '2a5e2b259a289475d62313e89b4643b3e912301d',
                'f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
                'fatal: update_ref failed for ref \'refs/heads/main\': cannot update ref \'refs/heads/main\': trying to write ref \'refs/heads/main\' with nonexistent object f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
            ]
        ]);

    it(
        'throws the UseCaseException and outputs fatal message, on oldobject does not match to currentvalue',
        function (string $ref, string $newValue, string $oldValue, string $currentValue, string $expected) {
            $reference = Reference::parse($ref);

            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($newValue)
                ->shouldReceive('getArgument')->with('arg2')->andReturn($oldValue);
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturn($reference)->once();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg(ObjectHash::parse($newValue)))->andReturn(true)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg(ObjectHash::parse($oldValue)))->andReturn(true)->once();
            $this->refRepository->shouldReceive('resolve')->withArgs(expectEqualArg($reference))->andReturn(ObjectHash::parse($currentValue))->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'refs/heads/main',
                '2a5e2b259a289475d62313e89b4643b3e912301d',
                'f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
                '2850271fb6886f538323bb62b8af78ae318529c7',
                'fatal: update_ref failed for ref \'refs/heads/main\': cannot lock ref \'refs/heads/main\': is at 2850271fb6886f538323bb62b8af78ae318529c7 but expected f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
            ]
        ]);
});

describe('__invoke::actionDelete', function () {
    it(
        'returns to success and delete reference',
        function (string $ref) {
            $reference = Reference::parse($ref);

            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(true)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturnNull();
            $this->refRepository
                ->shouldReceive('dereference')->with($ref)->andReturn($reference)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($reference))->andReturn(true)->once()
                ->shouldReceive('delete')->withArgs(expectEqualArg($reference))->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['refs/heads/main']
        ]);

    it(
        'returns to success and strict delete reference',
        function (string $ref, string $oldValue, string $currentValue) {
            $reference = Reference::parse($ref);
            $currentObject = ObjectHash::parse($currentValue);

            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(true)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($oldValue);
            $this->refRepository
                ->shouldReceive('dereference')->with($ref)->andReturn($reference)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($reference))->andReturn(true)->once()
                ->shouldReceive('resolve')->withArgs(expectEqualArg($reference))->andReturn($currentObject)->once()
                ->shouldReceive('delete')->withArgs(expectEqualArg($reference))->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'refs/heads/main',
                'f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
                'f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
            ]
        ]);

    it(
        'throws the UseCaseException and outputs fatal message on invalid reference',
        function (string $ref, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(true)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturnNull();
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturnNull()->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            ['no-reference', 'error: refusing to update ref with bad name \'no-reference\'']
        ]);

    it(
        'throws the UseCaseException and outputs fatal message on the oldvalue does not match to pattern of sha1',
        function (string $ref, string $oldValue, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(true)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($oldValue);
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturn(Reference::parse($ref))->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'refs/heads/main',
                'invalid-sha1',
                'fatal: invalid-sha1: not a valid SHA1'
            ]
        ]);

    it(
        'throws the UseCaseException and outputs fatal message on the oldvalue does not exists',
        function (string $ref, string $oldValue, string $expected) {
            $reference = Reference::parse($ref);

            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(true)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($oldValue);
            $this->refRepository
                ->shouldReceive('dereference')->with($ref)->andReturn($reference)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($reference))->andReturn(false)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'refs/heads/not-found',
                'f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
                'error: cannot lock ref \'refs/heads/not-found\': unable to resolve reference \'refs/heads/not-found\''
            ]
        ]);

    it(
        'throws the UseCaseException and outputs fatal message on the oldvalue does not match to currentvalue',
        function (string $ref, string $oldValue, string $currentValue, string $expected) {
            $reference = Reference::parse($ref);
            $currentObject = ObjectHash::parse($currentValue);

            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(true)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($oldValue);
            $this->refRepository
                ->shouldReceive('dereference')->with($ref)->andReturn($reference)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($reference))->andReturn(true)->once()
                ->shouldReceive('resolve')->withArgs(expectEqualArg($reference))->andReturn($currentObject)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'refs/heads/main',
                'f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
                '2850271fb6886f538323bb62b8af78ae318529c7',
                'error: cannot lock ref \'refs/heads/main\': is at 2850271fb6886f538323bb62b8af78ae318529c7 but expected f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
            ]
        ]);
});

describe('__invoke', function () {
    it(
        'returns an internal error and outputs stack trace on throws an exception',
        function (string $ref, string $newValue, Throwable $exception, Throwable $expected) {
            $reference = Reference::parse($ref);
            $newObject = ObjectHash::parse($newValue);

            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($newValue)
                ->shouldReceive('getArgument')->with('arg2')->andReturnNull();
            $this->refRepository->shouldReceive('dereference')->with($ref)->andReturn($reference)->once();
            $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg($newObject))->andReturn(true)->once();
            $this->refRepository->shouldReceive('createOrUpdate')->andThrow($exception)->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = GitUpdateRefRequest::new($this->input);
            $useCase = new GitUpdateRefUseCase($this->printer, $this->objectRepository, $this->refRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::InternalError);
        }
    )
        ->with([
            [
                'refs/heads/main',
                'f2d2e4a05de8b38009b338b2fa73a88b0f6c9d11',
                new RuntimeException('failed to updateOrCreate'),
                new RuntimeException('failed to updateOrCreate'),
            ]
        ]);
});
