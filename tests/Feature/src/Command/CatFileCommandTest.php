<?php

declare(strict_types=1);

use Phpgit\Command\CatFileCommand;
use Phpgit\Domain\BlobObject;
use Phpgit\Infra\Repository\ObjectRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\CommandRunner;

beforeAll(function () {
    CommandRunner::run('php git init');

    $objectRepository = new ObjectRepository;
    $blob = BlobObject::new("Hello, World!\n"); // 8ab686eafeb1f44702738c8b0f24f2567c36da6d
    $objectRepository->save($blob);
});

describe('cat-file', function () {
    it(
        'outputs object type',
        function () {
            $tester = new CommandTester(new CatFileCommand);
            $tester->execute([
                'object' => '8ab686eafeb1f44702738c8b0f24f2567c36da6d',
                '--type' => true,
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("blob\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );

    it(
        'outputs pretty print',
        function () {
            $tester = new CommandTester(new CatFileCommand);
            $tester->execute([
                'object' => '8ab686eafeb1f44702738c8b0f24f2567c36da6d',
                '--pretty-print' => true,
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("Hello, World!\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );

    it(
        'outputs result to exists file',
        function () {
            $tester = new CommandTester(new CatFileCommand);
            $tester->execute([
                'object' => '8ab686eafeb1f44702738c8b0f24f2567c36da6d',
                '--exists' => true,
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe('');
            expect($exitCode)->toBeExitSuccess();
        }
    );

    it(
        'outputs size',
        function () {
            $tester = new CommandTester(new CatFileCommand);
            $tester->execute([
                'object' => '8ab686eafeb1f44702738c8b0f24f2567c36da6d',
                '--size' => true,
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("14\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
