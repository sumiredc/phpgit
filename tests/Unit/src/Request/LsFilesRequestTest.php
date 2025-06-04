<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\LsFilesOptionAction;
use Phpgit\Request\LsFilesRequest;
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
                    'tag',
                    't',
                    InputOption::VALUE_NONE,
                    'Show status tags together with filenames.'
                )->once()
                ->shouldReceive('addOption')->with(
                    'zero',
                    'z',
                    InputOption::VALUE_NONE,
                    '\0 line termination on output and do not quote filenames.'
                )->once()
                ->shouldReceive('addOption')->with(
                    'stage',
                    's',
                    InputOption::VALUE_NONE,
                    'Show staged contents\' mode bits, object name and stage number in the output.'
                )->once()
                ->shouldReceive('addOption')->with(
                    'debug',
                    null,
                    InputOption::VALUE_NONE,
                    'After each line that describes a file, add more data about its cache entry. This is intended to show as much information as possible for manual inspection; the exact format may change at any time.'
                )->once();

            LsFilesRequest::setUp($this->command);

            $refClass = new ReflectionClass(LsFilesRequest::class);
            $assertNew = $refClass->getMethod('assertNew');
            $assertNew->invoke($refClass);

            expect(true)->toBeTrue();
        }
    );
});

describe('new', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(LsFilesRequest::class);
        $unlock = $refClass->getMethod('unlock');
        $unlock->invoke($refClass);
    });

    it(
        'is set "tag" in property of action',
        function (LsFilesOptionAction $expected) {
            $this->input->shouldReceive('getOption')->with('tag')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('zero')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('stage')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('debug')->andReturn(false)->once();

            $actual = LsFilesRequest::new($this->input);

            expect($actual->action)->toBe($expected);
        }
    )
        ->with([
            [LsFilesOptionAction::Tag]
        ]);

    it(
        'is set "zero" in property of action',
        function (LsFilesOptionAction $expected) {
            $this->input->shouldReceive('getOption')->with('tag')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('zero')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('stage')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('debug')->andReturn(false)->once();

            $actual = LsFilesRequest::new($this->input);

            expect($actual->action)->toBe($expected);
        }
    )
        ->with([
            [LsFilesOptionAction::Zero]
        ]);

    it(
        'is set "stage" in property of action',
        function (LsFilesOptionAction $expected) {
            $this->input->shouldReceive('getOption')->with('tag')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('zero')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('stage')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('debug')->andReturn(false)->once();

            $actual = LsFilesRequest::new($this->input);

            expect($actual->action)->toBe($expected);
        }
    )
        ->with([
            [LsFilesOptionAction::Stage]
        ]);

    it(
        'is set "debug" in property of action',
        function (LsFilesOptionAction $expected) {
            $this->input->shouldReceive('getOption')->with('tag')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('zero')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('stage')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('debug')->andReturn(true)->once();

            $actual = LsFilesRequest::new($this->input);

            expect($actual->action)->toBe($expected);
        }
    )
        ->with([
            [LsFilesOptionAction::Debug]
        ]);

    it(
        'is set "default" in property of action',
        function (LsFilesOptionAction $expected) {
            $this->input->shouldReceive('getOption')->andReturn(false)->times(4);

            $actual = LsFilesRequest::new($this->input);

            expect($actual->action)->toBe($expected);
        }
    )
        ->with([
            [LsFilesOptionAction::Default]
        ]);
});
