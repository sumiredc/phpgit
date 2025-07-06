<?php

declare(strict_types=1);

use Phpgit\Command\RevParseCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\CommandRunner;

beforeAll(function () {
    CommandRunner::run('php git init');
});

describe('rev-parse', function () {
    it(
        'outputs parse result',
        function () {
            file_put_contents(F_GIT_HEAD, "34dc30a3fdbd699c44ee894962bc07420ee26305\n");
            $tester = new CommandTester(new RevParseCommand);
            $tester->execute([
                'args' => ['HEAD'],
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("34dc30a3fdbd699c44ee894962bc07420ee26305\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
