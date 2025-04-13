<?php

namespace Phpgit\Lib;

use Throwable;

interface IOInterface
{
    public function stackTrace(Throwable $th): void;

    public function echo(string $message): void;

    public function success(string|array $message): void;

    public function error(string|array $message): void;

    public function warning(string|array $message): void;

    public function text(string|array $message): void;

    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void;

    public function writeln(string|iterable $messages, int $options = 0): void;
}
