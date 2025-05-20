<?php

declare(strict_types=1);

use Phpgit\Domain\CommandInput\LsFilesOptionAction;
use Phpgit\Request\LsFilesRequest;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
});

describe('new', function () {
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
