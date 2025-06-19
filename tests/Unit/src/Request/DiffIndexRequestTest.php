<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\DiffIndexOptionAction;
use Phpgit\Request\DiffIndexRequest;
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
                ->shouldReceive('addArgument')->with('tree-ish', InputArgument::REQUIRED, 'The id of a tree object to diff against.')->once()
                ->shouldReceive('addOption')->with('cached', null, InputOption::VALUE_NONE, 'Do not consider the on-disk file at all.')->once()
                ->shouldReceive('addOption')->with('stat', null, InputOption::VALUE_NONE, 'Generate a diffstat.')->once();

            DiffIndexRequest::setUp($this->command);

            $refClass = new ReflectionClass(DiffIndexRequest::class);
            $assertNew = $refClass->getMethod('assertNew');
            $assertNew->invoke($refClass);

            expect(true)->toBeTrue();
        }
    );
});

describe('new', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(DiffIndexRequest::class);
        $unlock = $refClass->getMethod('unlock');
        $unlock->invoke($refClass);
    });

    it(
        'match to args to properties',
        function (
            string $treeIsh,
            bool $cached,
            bool $stat,
            DiffIndexOptionAction $expectedAction,
            bool $expectedIsCached,
            string $expectedTreeIsh
        ) {
            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)->once()
                ->shouldReceive('getOption')->with('cached')->andReturn($cached)->once()
                ->shouldReceive('getOption')->with('stat')->andReturn($stat)->once();

            $actual = DiffIndexRequest::new($this->input);

            expect($actual->action)->toBe($expectedAction);
            expect($actual->isCached)->toBe($expectedIsCached);
            expect($actual->treeIsh)->toBe($expectedTreeIsh);
        }
    )
        ->with([
            [
                '80655da8d80aaaf92ce5357e7828dc09adb00993',
                true,
                false,
                DiffIndexOptionAction::Default,
                true,
                '80655da8d80aaaf92ce5357e7828dc09adb00993',
            ],
            [
                'HEAD',
                false,
                true,
                DiffIndexOptionAction::Stat,
                false,
                'HEAD',
            ],
        ]);
});
