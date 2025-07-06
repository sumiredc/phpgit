<?php

declare(strict_types=1);

use Phpgit\Command\InitCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\CommandRunner;

beforeEach(function () {
    CommandRunner::run('rm -rf /tmp/project/.git');
});

describe('init', function () {
    it(
        'outputs initialize message',
        function () {
            $tester = new CommandTester(new InitCommand);
            $tester->execute([]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("Initialized empty Git repository in /tmp/project/.git\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );

    it(
        'outputs reinitialize message on already .git directory',
        function () {
            $tester = new CommandTester(new InitCommand);
            $tester->execute([]);
            $tester->execute([]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("Reinitialized existing Git repository in /tmp/project/.git/\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
