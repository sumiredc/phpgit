<?php

declare(strict_types=1);

use Phpgit\Domain\CommandInput\UpdateIndexOptionAction;
use Phpgit\Request\UpdateIndexRequest;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
});

describe('new', function () {
    it(
        'is set "add" for action when unspecifies to option',
        function (
            ?string $file,
            UpdateIndexOptionAction $expectedAction,
            string $expectedFile
        ) {
            $this->input->shouldReceive('getOption')->andReturn(false)->times(4);

            $this->input->shouldReceive('getArgument')->with('mode')->andReturn($file)->once();
            $this->input->shouldReceive('getArgument')->with('object')->never();
            $this->input->shouldReceive('getArgument')->with('file')->never();

            $actual = UpdateIndexRequest::new($this->input);

            expect($actual->action)->toBe($expectedAction);
            expect($actual->file)->toBe($expectedFile);
        }
    )
        ->with([
            'specifies to file' => [
                'file' => 'README.md',
                'expectedAction' => UpdateIndexOptionAction::Add,
                'expectedFile' => 'README.md'
            ],
            'unspecifies to file' => [
                'file' => null,
                'expectedAction' => UpdateIndexOptionAction::Add,
                'expectedFile' => ''
            ]
        ]);

    it(
        'is set "add" for action when specifies to "add" for option',
        function (
            ?string $file,
            UpdateIndexOptionAction $expectedAction,
            string $expectedFile
        ) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false)->once();

            $this->input->shouldReceive('getArgument')->with('mode')->andReturn($file)->once();
            $this->input->shouldReceive('getArgument')->with('object')->never();
            $this->input->shouldReceive('getArgument')->with('file')->never();

            $actual = UpdateIndexRequest::new($this->input);

            expect($actual->action)->toBe($expectedAction);
            expect($actual->file)->toBe($expectedFile);
        },
    )
        ->with([
            'specifies to file' => [
                'file' => 'README.md',
                'expectedAction' => UpdateIndexOptionAction::Add,
                'expectedFile' => 'README.md'
            ],
            'unspecifies to file' => [
                'file' => null,
                'expectedAction' => UpdateIndexOptionAction::Add,
                'expectedFile' => ''
            ]
        ]);

    it(
        'is set "remove" for action when specifies to "remove" for option',
        function (
            ?string $file,
            UpdateIndexOptionAction $expectedAction,
            string $expectedFile
        ) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false)->once();

            $this->input->shouldReceive('getArgument')->with('mode')->andReturn($file)->once();
            $this->input->shouldReceive('getArgument')->with('object')->never();
            $this->input->shouldReceive('getArgument')->with('file')->never();

            $actual = UpdateIndexRequest::new($this->input);

            expect($actual->action)->toBe($expectedAction);
            expect($actual->file)->toBe($expectedFile);
        },
    )
        ->with([
            'specifies to file' => [
                'file' => 'README.md',
                'expectedAction' => UpdateIndexOptionAction::Remove,
                'expectedFile' => 'README.md'
            ],
            'unspecifies to file' => [
                'file' => null,
                'expectedAction' => UpdateIndexOptionAction::Remove,
                'expectedFile' => ''
            ]
        ]);

    it(
        'is set "force-remove" for action when specifies to "force-remove" for option',
        function (
            ?string $file,
            UpdateIndexOptionAction $expectedAction,
            string $expectedFile
        ) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false)->once();

            $this->input->shouldReceive('getArgument')->with('mode')->andReturn($file)->once();
            $this->input->shouldReceive('getArgument')->with('object')->never();
            $this->input->shouldReceive('getArgument')->with('file')->never();

            $actual = UpdateIndexRequest::new($this->input);

            expect($actual->action)->toBe($expectedAction);
            expect($actual->file)->toBe($expectedFile);
        },
    )
        ->with([
            'specifies to file' => [
                'file' => 'README.md',
                'expectedAction' => UpdateIndexOptionAction::ForceRemove,
                'expectedFile' => 'README.md'
            ],
            'unspecifies to file' => [
                'file' => null,
                'expectedAction' => UpdateIndexOptionAction::ForceRemove,
                'expectedFile' => ''
            ]
        ]);

    it(
        'is set "cacheinfo" for action when specifies to "cacheinfo" for option',
        function (
            int $mode,
            string $object,
            string $file,
            UpdateIndexOptionAction $expectedAction,
            string $expectedFile
        ) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(true)->once();

            $this->input->shouldReceive('getArgument')->with('mode')->andReturn($mode)->once();
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object)->once();
            $this->input->shouldReceive('getArgument')->with('file')->andReturn($file)->once();

            $actual = UpdateIndexRequest::new($this->input);

            expect($actual->action)->toBe($expectedAction);
            expect($actual->file)->toBe($expectedFile);
        },
    )
        ->with([
            [
                'mode' => 100755,
                'object' => 'c9a291475b1bcaa4aa0c4cf459c29c2c52078949',
                'file' => 'README.md',
                'expectedAction' => UpdateIndexOptionAction::Cacheinfo,
                'expectedFile' => 'README.md'
            ],
            [
                'mode' => 100644,
                'object' => '403c716ea737afeb54f40549cdf5727f10ba6f18',
                'file' => 'src/app.php',
                'expectedAction' => UpdateIndexOptionAction::Cacheinfo,
                'expectedFile' => 'src/app.php'
            ],
        ]);

    it(
        'specifies cacheinfo and not enough of args on throws an exception',
        function (?int $mode, ?string $object, ?string $file, Throwable $expected) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(true)->once();

            $this->input->shouldReceive('getArgument')->with('mode')->andReturn($mode)->once();
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object)->once();
            $this->input->shouldReceive('getArgument')->with('file')->andReturn($file)->once();

            expect(fn() => UpdateIndexRequest::new($this->input))->toThrow($expected);
        }
    )
        ->with([
            'not enough of mode' => [
                'mode' => null,
                'object' => 'c9a291475b1bcaa4aa0c4cf459c29c2c52078949',
                'file' => 'README.md',
                'expected' => new InvalidArgumentException('error: option \'cacheinfo\' expects <mode>,<sha1>,<path>')
            ],
            'not enough of object' => [
                'mode' => 100755,
                'object' => null,
                'file' => 'README.md',
                'expected' => new InvalidArgumentException('error: option \'cacheinfo\' expects <mode>,<sha1>,<path>')
            ],
            'not enough of file' => [
                'mode' => 100644,
                'object' => 'c9a291475b1bcaa4aa0c4cf459c29c2c52078949',
                'file' => null,
                'expected' => new InvalidArgumentException('error: option \'cacheinfo\' expects <mode>,<sha1>,<path>')
            ]
        ]);
});
