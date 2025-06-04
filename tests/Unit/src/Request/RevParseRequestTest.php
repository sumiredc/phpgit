<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Request\RevParseRequest;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->command = Mockery::mock(CommandInterface::class);
    $this->input = Mockery::mock(InputInterface::class);
});

describe('setUp', function () {
    it(
        'calls setup args function',
        function () {
            $this->command
                ->shouldReceive('addArgument')
                ->with('args', InputArgument::IS_ARRAY, 'Separated by space')
                ->once();

            RevParseRequest::setUp($this->command);

            $refClass = new ReflectionClass(RevParseRequest::class);
            $assertNew = $refClass->getMethod('assertNew');
            $assertNew->invoke($refClass);

            expect(true)->toBeTrue();
        }
    );
});


describe('new', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(RevParseRequest::class);
        $unlock = $refClass->getMethod('unlock');
        $unlock->invoke($refClass);
    });

    it(
        'matches to args array to property',
        function (array $args) {
            $this->input->shouldReceive('getArgument')->with('args')->andReturn($args)->once();

            $actual = RevParseRequest::new($this->input);

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

            expect(fn() => RevParseRequest::new($this->input))->toThrow($expected);
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
