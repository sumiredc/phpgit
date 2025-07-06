<?php

declare(strict_types=1);

use Phpgit\Command\HashObjectCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\CommandRunner;

beforeAll(function () {
    CommandRunner::run('php git init');
});

describe('hash-object', function () {
    it(
        'outputs object hash string',
        function () {
            file_put_contents(F_GIT_TRACKING_ROOT . '/README.md', "Hello, World!\n");

            $tester = new CommandTester(new HashObjectCommand);
            $tester->execute([
                'file' => 'README.md',
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("8ab686eafeb1f44702738c8b0f24f2567c36da6d\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
