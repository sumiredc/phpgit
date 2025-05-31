<?php

declare(strict_types=1);

namespace Tests;

final class CommandRunner
{
    public static function run(string $command): CommandResult
    {
        $output = [];
        $exitCode = 0;

        exec("$command 2>&1", $output, $exitCode);

        return new CommandResult(implode("\n", $output), $exitCode);
    }
}
