<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Request\CommitRequest;
use Symfony\Component\Console\Exception\InvalidOptionException;
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
                ->shouldReceive('addOption')->with(
                    'message',
                    'm',
                    InputOption::VALUE_REQUIRED,
                    'A paragraph in the commit log message. This can be given more than once and each <message> becomes its own paragraph.'
                )->once();

            CommitRequest::setUp($this->command);

            $refClass = new ReflectionClass(CommitRequest::class);
            $assertNew = $refClass->getMethod('assertNew');
            $assertNew->invoke($refClass);

            expect(true)->toBeTrue();
        }
    );
});

describe('new', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(CommitRequest::class);
        $unlock = $refClass->getMethod('unlock');
        $unlock->invoke($refClass);
    });

    it(
        'matches to args to properties',
        function (
            string $message,
            string $expectedMessage,
        ) {
            $this->input
                ->shouldReceive('getOption')->with('message')->andReturn($message)->once();

            $actual = CommitRequest::new($this->input);

            expect($actual->message)->toBe($expectedMessage);
        }
    )
        ->with([
            ['first commit', 'first commit'],
            ['second commit', 'second commit'],
        ]);

    it(
        'throws an exceptions on unspecifies empty',
        function (?string $message, Throwable $expected) {
            $this->input
                ->shouldReceive('getOption')->with('message')->andReturn($message)->once();

            expect(fn() => CommitRequest::new($this->input))->toThrow($expected);
        }
    )
        ->with([
            'message is empty' => [
                '',
                new InvalidOptionException('Not enough options (missing: "message").'),
            ],
            'message is null' => [
                null,
                new InvalidOptionException('Not enough options (missing: "message").'),
            ],
        ]);
});

describe('new: fails case', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(CommitRequest::class);
        $lock = $refClass->getMethod('lock');
        $lock->invoke($refClass);
    });

    it(
        'throws an exception on did call setUp method',
        function (Throwable $expected) {
            expect(fn() => CommitRequest::new($this->input))->toThrow($expected);
        }
    )
        ->with([
            [new LogicException('Cannot instantiate request. Call setUp() first')]
        ]);
});
