<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\GitUpdateRefOptionAction;
use Phpgit\Request\GitUpdateRefRequest;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

beforeEach(function () {
    $this->command = Mockery::mock(CommandInterface::class);
    $this->input = Mockery::mock(InputInterface::class);
});

describe('setUp', function () {
    it(
        'calls setup args function',
        function () {
            $this->command
                ->shouldReceive('addOption')->with('delete', 'd', InputOption::VALUE_NONE)->once()
                ->shouldReceive('addArgument')->with('ref', InputArgument::REQUIRED)->once()
                ->shouldReceive('addArgument')->with('arg1', InputArgument::OPTIONAL, 'update: <newvalue:REQUIRED>, delete: <oldvalue:OPTIONAL>')->once()
                ->shouldReceive('addArgument')->with('arg2', InputArgument::OPTIONAL, 'update: <oldvalue:OPTIONAL>')->once();

            GitUpdateRefRequest::setUp($this->command);

            $refClass = new ReflectionClass(GitUpdateRefRequest::class);
            $assertNew = $refClass->getMethod('assertNew');
            $assertNew->invoke($refClass);

            expect(true)->toBeTrue();
        }
    );
});

describe('new', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(GitUpdateRefRequest::class);
        $unlock = $refClass->getMethod('unlock');
        $unlock->invoke($refClass);
    });

    it(
        'match to args to properties on execute udpate command',
        function (
            string $ref,
            string $arg1,
            ?string $arg2,
            string $expectedRef,
            string $expectedNewValue,
            string $expectedOldValue
        ) {
            $this->command->shouldReceive([
                'addOption' => $this->command,
                'addArgument' => $this->command,
            ]);
            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(false)->once()
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)->once()
                ->shouldReceive('getArgument')->with('arg1')->andReturn($arg1)->once()
                ->shouldReceive('getArgument')->with('arg2')->andReturn($arg2)->once();

            GitUpdateRefRequest::setUp($this->command);
            $actual = GitUpdateRefRequest::new($this->input);

            expect($actual->action)->toBe(GitUpdateRefOptionAction::Update);
            expect($actual->ref)->toBe($expectedRef);
            expect($actual->newValue)->toBe($expectedNewValue);
            expect($actual->oldValue)->toBe($expectedOldValue);
        }
    )
        ->with([
            'specifies arg1' => [
                'ref' => 'HEAD',
                'arg1' => '2a5e2b259a289475d62313e89b4643b3e912301d',
                'arg2' => null,
                'expectedRef' => 'HEAD',
                'expectedNewValue' => '2a5e2b259a289475d62313e89b4643b3e912301d',
                'expectedOldValue' => '',
            ],
            'specifies arg1 and arg2' => [
                'ref' => 'refs/heads/main',
                'arg1' => '1ba22fe903f70cf262ecae71abdf5fe4f51a7b86',
                'arg2' => 'efdc2754b9ef53130d58e6fe5a034d525e07ac04',
                'expectedRef' => 'refs/heads/main',
                'expectedNewValue' => '1ba22fe903f70cf262ecae71abdf5fe4f51a7b86',
                'expectedOldValue' => 'efdc2754b9ef53130d58e6fe5a034d525e07ac04',
            ]
        ]);

    it(
        'match to args to properties on execute delete command',
        function (
            string $ref,
            ?string $arg1,
            string $expectedRef,
            string $expectedOldValue
        ) {
            $this->command->shouldReceive([
                'addOption' => $this->command,
                'addArgument' => $this->command,
            ]);
            $this->input
                ->shouldReceive('getOption')->with('delete')->andReturn(true)->once()
                ->shouldReceive('getArgument')->with('ref')->andReturn($ref)->once()
                ->shouldReceive('getArgument')->with('arg1')->andReturn($arg1)->once()
                ->shouldReceive('getArgument')->with('arg2')->never();

            GitUpdateRefRequest::setUp($this->command);
            $actual = GitUpdateRefRequest::new($this->input);

            expect($actual->action)->toBe(GitUpdateRefOptionAction::Delete);
            expect($actual->ref)->toBe($expectedRef);
            expect($actual->newValue)->toBeNull();
            expect($actual->oldValue)->toBe($expectedOldValue);
        }
    )
        ->with([
            'unspecifies arg1' => [
                'ref' => 'HEAD',
                'arg1' => null,
                'expectedRef' => 'HEAD',
                'expectedOldValue' => '',
            ],
            'specifies arg1' => [
                'ref' => 'refs/heads/main',
                'arg1' => '1ba22fe903f70cf262ecae71abdf5fe4f51a7b86',
                'expectedRef' => 'refs/heads/main',
                'expectedOldValue' => '1ba22fe903f70cf262ecae71abdf5fe4f51a7b86',
            ]
        ]);
});

describe('new: fails case', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(GitUpdateRefRequest::class);
        $lock = $refClass->getMethod('lock');
        $lock->invoke($refClass);
    });

    it(
        'throws an exception on did call setUp method',
        function (Throwable $expected) {
            expect(fn() => GitUpdateRefRequest::new($this->input))->toThrow($expected);
        }
    )
        ->with([
            [new LogicException('Cannot instantiate request. Call setUp() first')]
        ]);
});
