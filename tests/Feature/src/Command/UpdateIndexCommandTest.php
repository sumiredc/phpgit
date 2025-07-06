<?php

declare(strict_types=1);

use Phpgit\Command\UpdateIndexCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\CommandRunner;

beforeAll(function () {
    CommandRunner::run('php git init');
});

describe('update-index', function () {
    it(
        'returns exit code success',
        function () {
            file_put_contents(F_GIT_TRACKING_ROOT . '/README.md', "Hello, World!\n");
            $tester = new CommandTester(new UpdateIndexCommand);
            $tester->execute([
                '--add' => true,
                'arg1' => 'README.md'
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe('');
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
