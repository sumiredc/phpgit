<?php

declare(strict_types=1);

use Phpgit\Infra\Printer\CliPrinter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

describe('stackTrace', function () {
    it(
        'matches to output console stack trace',
        function (Throwable $th) {
            $input = new ArrayInput([]);
            $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, false);

            $printer = new CliPrinter($input, $output);
            $printer->stackTrace($th);
            $actual = $output->fetch();

            expect($actual)->toContain('[ERROR]');
            expect($actual)->toContain($th->getMessage());
            expect($actual)->toContain($th->getFile());
        }
    )
        ->with([
            [new RuntimeException],
            [new InvalidArgumentException],
        ]);
});

describe('echo', function () {
    it(
        'matches to output console string',
        function (string $message) {
            $input = new ArrayInput([]);
            $output = new NullOutput();

            $printer = new CliPrinter($input, $output);

            ob_start();
            $printer->echo($message);
            $actual = ob_get_clean();

            expect($actual)->toBe($message);
        }
    )
        ->with([
            ['Initialized empty Git repository in phpgit'],
            ['Reinitialized empty Git repository in phpgit']
        ]);
});

describe('writelnDiffStat', function () {
    it(
        'matches to output console string',
        function (
            int $pathLength,
            int $diffDigits,
            string $path,
            int $insertions,
            int $deletions,
            string $expected,
        ) {
            $input = new ArrayInput([]);
            $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, false);

            $printer = new CliPrinter($input, $output);

            $printer->writelnDiffStat($pathLength, $diffDigits, $path, $insertions, $deletions);
            $actual = $output->fetch();

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [
                'pathLength' => 15,
                'diffDigits' => 3,
                'path' => 'dummy-path',
                'insertions' => 6,
                'deletions' => 12,
                'expected' => " dummy-path      |  18 \e[32m++++++\e[39m\e[31m------------\e[39m\n",
            ],
        ]);
});
