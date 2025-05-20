<?php

declare(strict_types=1);

use Phpgit\Infra\Printer\CliPrinter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

describe('stackTrace', function () {
    it('should match to output console stack trace', function (Throwable $th) {
        $input = new ArrayInput([]);
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, false);

        $printer = new CliPrinter($input, $output);
        $printer->stackTrace($th);
        $actual = $output->fetch();

        expect($actual)->toContain('[ERROR]');
        expect($actual)->toContain($th->getMessage());
        expect($actual)->toContain($th->getFile());
    })
        ->with([
            [new RuntimeException],
            [new InvalidArgumentException],
        ]);
});


describe('echo', function () {
    it('should match to output console string', function (string $message) {
        $input = new ArrayInput([]);
        $output = new NullOutput();

        $printer = new CliPrinter($input, $output);

        ob_start();
        $printer->echo($message);
        $actual = ob_get_clean();

        expect($actual)->toBe($message);
    })
        ->with([
            ['Initialized empty Git repository in phpgit'],
            ['Reinitialized empty Git repository in phpgit']
        ]);
});
