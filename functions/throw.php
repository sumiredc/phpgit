<?php

declare(strict_types=1);

/**
 * @template R
 * @template T of Throwable
 * 
 * @param callable(): R $callback
 * @param class-string<T> $replaceException
 * @return R
 * @throws T
 * @throws InvalidArgumentException
 */

function try_or_throw(
    callable $callback,
    string $replaceException,
    ?string $message = null,
    ?int $code = null
) {
    if (!is_subclass_of($replaceException, Throwable::class)) {
        throw new InvalidArgumentException(sprintf(
            'Not allows specifies exceptions: %s',
            $replaceException
        ));
    }

    try {
        return $callback();
    } catch (Throwable $th) {
        $message ??= $th->getMessage();
        $code ??= $th->getCode();

        throw new $replaceException($message, $code, $th->getPrevious());
    }
}

/**
 * @template T of Throwable
 * 
 * @param T $th
 * @throws T
 */
function throw_if(bool $condition, Throwable $th): void
{
    if ($condition) {
        throw $th;
    }
}

/**
 * @template T of Throwable
 * 
 * @param T $th
 * @throws T
 */
function throw_unless(bool $condition, Throwable $th): void
{
    if ($condition) {
        return;
    }

    throw $th;
}
