<?php

declare(strict_types=1);

use Phpgit\Exception\UseCaseException;

describe('try_or_throw', function () {
    it(
        'returns to callback result',
        function (callable $callback, string $th, mixed $expected) {
            $actual = try_or_throw($callback, $th);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [fn() => 1, UnderflowException::class, 1],
            [fn() => 'dummy', RuntimeException::class, 'dummy'],
            [fn() => 0.5, OverflowException::class, 0.5],
            [fn() => true, TypeError::class, true],
        ]);

    it(
        'maps a thrown exception to the specified exception class',
        function (
            callable $callback,
            string $th,
            ?string $message,
            ?int $code,
            Throwable $expected
        ) {
            expect(fn() => try_or_throw($callback, $th, $message, $code, $expected))
                ->toThrow($expected);
        }
    )
        ->with([
            [
                fn() => throw new UnderflowException('Underflowed', 400),
                UseCaseException::class,
                'Failed',
                500,
                new UseCaseException('Failed', 500)
            ],
            [
                fn() => throw new OverflowException('Overflowed', 400),
                RuntimeException::class,
                null,
                null,
                new RuntimeException('Overflowed', 400)
            ],
        ]);

    it(
        'throws the InvalidArugmentException on replace exception does not extend Throwable',
        function (string $th, Throwable $expected) {
            expect(fn() => try_or_throw(fn() => throw new Exception, $th))
                ->toThrow($expected);
        }
    )
        ->with([
            [
                stdClass::class,
                new InvalidArgumentException('Not allows specifies exceptions: stdClass')
            ],
            [
                Reflection::class,
                new InvalidArgumentException('Not allows specifies exceptions: Reflection')
            ],
        ]);
});

describe('throw_if', function () {
    it(
        'throws specifies an exception on condition is true',
        function (bool $condition, Throwable $th, Throwable $expected) {
            expect(fn() => throw_if($condition, $th))->toThrow($expected);
        }
    )
        ->with([
            [true, new TypeError('failed type'), new TypeError('failed type')],
            [true, new RuntimeException('failed someting'), new RuntimeException('failed someting')],
        ]);

    it(
        'unthrows specifies an exception on condition is false',
        function (bool $condition, Throwable $th) {
            throw_if($condition, $th);

            expect(true)->toBeTrue();
        }
    )
        ->with([
            [false, new OverflowException('overflowed')],
            [false, new UnderflowException('underflowed')],
        ]);
});

describe('throw_unless', function () {
    it(
        'throws specifies an exception on condition is false',
        function (bool $condition, Throwable $th, Throwable $expected) {
            expect(fn() => throw_unless($condition, $th))->toThrow($expected);
        }
    )
        ->with([
            [false, new OverflowException('overflowed'), new OverflowException('overflowed')],
            [false, new UnderflowException('underflowed'), new UnderflowException('underflowed')],
        ]);

    it(
        'unthrows specifies an exception on condition is true',
        function (bool $condition, Throwable $th) {
            throw_unless($condition, $th);

            expect(true)->toBeTrue();
        }
    )
        ->with([
            [true, new TypeError('failed type')],
            [true, new RuntimeException('failed someting')],
        ]);
});
