<?php

declare(strict_types=1);

use Phpgit\Command\AddCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\CommandRunner;

beforeAll(function () {
    CommandRunner::run('php git init');
});

describe('add', function () {
    it(
        'returns exit code of success and outputs empty',
        function () {
            file_put_contents(F_GIT_TRACKING_ROOT . '/sample.md', 'sample');

            $tester = new CommandTester(new AddCommand);
            $tester->execute([
                'path' => 'sample.md',
                '--all' => false,
                '--update' => false,
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe('');
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
