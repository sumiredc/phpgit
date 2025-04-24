<?php

declare(strict_types=1);

use Phpgit\Lib\IO;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

describe('stackTrace', function () {
    it('should match to output console stack trace', function (Throwable $th) {
        $input = new ArrayInput([]);
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, false);

        $io = new IO($input, $output);
        $io->stackTrace($th);
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

        $io = new IO($input, $output);

        ob_start();
        $io->echo($message);
        $actual = ob_get_clean();

        expect($actual)->toBe($message);
    })
        ->with([
            ['Initialized empty Git repository in phpgit'],
            ['Reinitialized empty Git repository in phpgit']
        ]);
});
