<?php

declare(strict_types=1);

use Phpgit\Domain\CommandInput\CatFileOptionType;
use Phpgit\Request\CatFileRequest;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
});

describe('new', function () {
    it(
        'is set the "type" in property of type',
        function (string $object, CatFileOptionType $expected) {
            $this->input->shouldReceive('getOption')->with('type')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(false)->once();
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object)->once();

            $actual = CatFileRequest::new($this->input);

            expect($actual->type)->toBe($expected);
            expect($actual->object)->toBe($object);
        }
    )
        ->with([
            ['7325eb186677325ce158c51f203f3d026e48803b', CatFileOptionType::Type]
        ]);

    it(
        'is set the "size" in property of type',
        function (string $object, CatFileOptionType $expected) {
            $this->input->shouldReceive('getOption')->with('size')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(false)->once();
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object)->once();

            $actual = CatFileRequest::new($this->input);

            expect($actual->type)->toBe($expected);
            expect($actual->object)->toBe($object);
        }
    )
        ->with([
            ['235dfc9ddd37a18f1f6638d80e146252020cb4a8', CatFileOptionType::Size]
        ]);

    it(
        'is set the "exists" in property of type',
        function (string $object, CatFileOptionType $expected) {
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(false)->once();
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object)->once();

            $actual = CatFileRequest::new($this->input);

            expect($actual->type)->toBe($expected);
            expect($actual->object)->toBe($object);
        }
    )
        ->with([
            ['34a2d4555e37ca2ad68563f0ce17d327b8bc0301', CatFileOptionType::Exists]
        ]);

    it(
        'is set the "pretty-print" in property of type',
        function (string $object, CatFileOptionType $expected) {
            $this->input->shouldReceive('getOption')->with('pretty-print')->andReturn(true)->once();
            $this->input->shouldReceive('getOption')->with('type')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('size')->andReturn(false)->once();
            $this->input->shouldReceive('getOption')->with('exists')->andReturn(false)->once();
            $this->input->shouldReceive('getArgument')->with('object')->andReturn($object)->once();

            $actual = CatFileRequest::new($this->input);

            expect($actual->type)->toBe($expected);
            expect($actual->object)->toBe($object);
        }
    )
        ->with([
            ['726c09f30127b66229f7d091ae51396060a3cdeb', CatFileOptionType::PrettyPrint]
        ]);

    it(
        'unspecifies for type on throws an exception',
        function (Throwable $expected) {
            $this->input->shouldReceive('getOption')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('object')
                ->andReturn('829c3804401b0727f70f73d4415e162400cbe57b');

            expect(fn() => CatFileRequest::new($this->input))->toThrow($expected);
        }
    )
        ->with([
            [new InvalidOptionException('Not enough options')]
        ]);
});
