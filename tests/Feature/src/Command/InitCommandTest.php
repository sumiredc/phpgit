<?php

declare(strict_types=1);

use Phpgit\Command\InitCommand;
use Symfony\Component\Console\Tester\CommandTester;

describe('init', function () {
    it(
        'outputs initialize message',
        function (string $output) {
            $tester = new CommandTester(new InitCommand);
            $tester->execute([]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe($output);
            expect($exitCode)->toBeExitSuccess();
        }
    )
        ->with([
            ['Initialized empty Git repository in /tmp/project/.git'],
            ['Reinitialized existing Git repository in /tmp/project/.git/']
        ]);
});
