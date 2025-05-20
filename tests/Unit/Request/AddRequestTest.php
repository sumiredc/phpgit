<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\AddOptionAction;
use Phpgit\Request\AddRequest;
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
                    'path',
                    InputArgument::OPTIONAL,
                    "[--all: unnecessary] don\'t use argument\n"
                        . '[other: required] relative path from project root',
                    ''
                )->once()
                ->shouldReceive('addOption')->with('all', 'A', InputOption::VALUE_NONE)->once();

            AddRequest::setUp($this->command);

            $refClass = new ReflectionClass(AddRequest::class);
            $assertNew = $refClass->getMethod('assertNew');
            $assertNew->invoke($refClass);

            expect(true)->toBeTrue();
        }
    );
});

describe('new', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(AddRequest::class);
        $unlock = $refClass->getMethod('unlock');
        $unlock->invoke($refClass);
    });

    it(
        'match to args to properties',
        function (
            string $path,
            bool $all,
            AddOptionAction $expectedAction,
            string $expectedPath
        ) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn($all)->once()
                ->shouldReceive('getArgument')->with('path')->andReturn($path)->once();

            $actual = AddRequest::new($this->input);

            expect($actual->action)->toBe($expectedAction);
            expect($actual->path)->toBe($expectedPath);
        }
    )
        ->with([
            'specifies path' => ['dummy/path', false, AddOptionAction::Default, 'dummy/path'],
            'specifies all option' => ['', true, AddOptionAction::All, '']
        ]);

    it(
        'throws an exception, on unspecifies all option and path',
        function () {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturnFalse()->once()
                ->shouldReceive('getArgument')->with('path')->andReturn('')->once();

            expect(fn() => AddRequest::new($this->input))
                ->toThrow(new InvalidArgumentException('Not enough options (missing: "path").'));
        }
    );
});

describe('new: fails case', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(AddRequest::class);
        $lock = $refClass->getMethod('lock');
        $lock->invoke($refClass);
    });

    it(
        'throws an exception on did call setUp method',
        function (Throwable $expected) {
            expect(fn() => AddRequest::new($this->input))->toThrow($expected);
        }
    )
        ->with([
            [new LogicException('Cannot instantiate request. Call setUp() first')]
        ]);
});
