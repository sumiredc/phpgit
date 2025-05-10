<?php

declare(strict_types=1);

use Phpgit\Request\GitRevParseRequest;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
});

describe('new', function () {
    it(
        'matches to args array to property',
        function (array $args) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();

            $actual = GitRevParseRequest::new($this->input);

            expect($actual->args)->toBe($args);
        }
    )
        ->with([
            [['README.md', 'main.rs', 'main.go']],
            [[]],
        ]);

    it(
        'throws an exception on args is not an array',
        function (mixed $args, Throwable $expected) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();

            expect(fn() => GitRevParseRequest::new($this->input))->toThrow($expected);
        }
    )
        ->with([
            'is string' => [
                'dummy text',
                new InvalidArgumentException('invalid argument because it is not an array: string')
            ],
            'is integer' => [
                123,
                new InvalidArgumentException('invalid argument because it is not an array: integer')
            ],
            'is double' => [
                1.23,
                new InvalidArgumentException('invalid argument because it is not an array: double')
            ],
            'is object' => [
                new stdClass,
                new InvalidArgumentException('invalid argument because it is not an array: object')
            ],
            'is boolean' => [
                true,
                new InvalidArgumentException('invalid argument because it is not an array: boolean')
            ],
        ]);
});
