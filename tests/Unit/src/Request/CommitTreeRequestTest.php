<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Request\CommitTreeRequest;
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
                ->shouldReceive('addArgument')->with(
                    'tree',
                    InputArgument::REQUIRED,
                    'An existing tree object.'
                )->once()
                ->shouldReceive('addOption')->with(
                    'message',
                    'm',
                    InputOption::VALUE_REQUIRED,
                    'A paragraph in the commit log message. This can be given more than once and each <message> becomes its own paragraph.'
                )->once()
                ->shouldReceive('addOption')->with(
                    'parent',
                    'p',
                    InputOption::VALUE_REQUIRED,
                    'Each -p indicates the id of a parent commit object.'
                )->once();

            CommitTreeRequest::setUp($this->command);

            $refClass = new ReflectionClass(CommitTreeRequest::class);
            $assertNew = $refClass->getMethod('assertNew');
            $assertNew->invoke($refClass);

            expect(true)->toBeTrue();
        }
    );
});

describe('new', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(CommitTreeRequest::class);
        $unlock = $refClass->getMethod('unlock');
        $unlock->invoke($refClass);
    });

    it(
        'match to args to properties',
        function (
            string $tree,
            string $message,
            ?string $parent,
            string $expectedTree,
            string $expectedMessage,
            string $expectedParent,
        ) {
            $this->input
                ->shouldReceive('getArgument')->with('tree')->andReturn($tree)->once()
                ->shouldReceive('getOption')->with('message')->andReturn($message)->once()
                ->shouldReceive('getOption')->with('parent')->andReturn($parent)->once();

            $actual = CommitTreeRequest::new($this->input);

            expect($actual->tree)->toBe($expectedTree);
            expect($actual->message)->toBe($expectedMessage);
            expect($actual->parent)->toBe($expectedParent);
        }
    )
        ->with([
            'parent is null' => [
                '081b3bbbc244693f20cf87f9de45db666faa4dc8',
                'first commit',
                null,
                '081b3bbbc244693f20cf87f9de45db666faa4dc8',
                'first commit',
                '',
            ],
            'parent is hash' => [
                'fc01489d8afd08431c7245b4216ea9d01856c3b9',
                'second commit',
                'cc7caef76a35b0192084344f2b4d8a4d182ad9a1',
                'fc01489d8afd08431c7245b4216ea9d01856c3b9',
                'second commit',
                'cc7caef76a35b0192084344f2b4d8a4d182ad9a1',
            ],
        ]);

    it(
        'throws an exceptions on unspecifies empty',
        function (
            ?string $message,
            Throwable $expected
        ) {
            $this->input
                ->shouldReceive('getArgument')->with('tree')->andReturn('dummy-tree-hash')->once()
                ->shouldReceive('getOption')->with('message')->andReturn($message)->once()
                ->shouldReceive('getOption')->with('parent')->never();

            expect(fn() => CommitTreeRequest::new($this->input))->toThrow($expected);
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
        $refClass = new ReflectionClass(CommitTreeRequest::class);
        $lock = $refClass->getMethod('lock');
        $lock->invoke($refClass);
    });

    it(
        'throws an exception on did call setUp method',
        function (Throwable $expected) {
            expect(fn() => CommitTreeRequest::new($this->input))->toThrow($expected);
        }
    )
        ->with([
            [new LogicException('Cannot instantiate request. Call setUp() first')]
        ]);
});
