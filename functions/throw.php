<?php

declare(strict_types=1);

/**
 * @template R
 * @template T of Throwable
 * 
 * @param callable(): R $callback
 * @param T|class-string<T>|null $replaceException
 * @return R
 * @throws T
 * @throws InvalidArgumentException
 */

function try_or_throw(callable $callback, Throwable|string|null $replaceException = null)
{
    if (
        !is_null($replaceException)
        && !is_subclass_of($replaceException, Throwable::class)
    ) {
        throw new InvalidArgumentException(sprintf('Not allows specifies exceptions: $s', $replaceException));
    }

    try {
        return $callback();
    } catch (Throwable $th) {
        if (is_null($replaceException)) {
            throw $th;
        }

        if (is_object($replaceException)) {
            throw $replaceException;
        }

        throw new $replaceException($th->getMessage(), $th->getCode(), $th->getPrevious());
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
