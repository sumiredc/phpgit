<?php

declare(strict_types=1);

use Phpgit\Command\DiffIndexCommand;
use Phpgit\Domain\BlobObject;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\TrackedPath;
use Phpgit\Infra\Repository\IndexRepository;
use Phpgit\Infra\Repository\ObjectRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\CommandRunner;
use Tests\Factory\FileStatFactory;

beforeAll(function () {
    CommandRunner::run('php git init');

    file_put_contents(F_GIT_TRACKING_ROOT . '/README.md', "Hello, World!\n");

    $objectRepository = new ObjectRepository;
    $blob = BlobObject::new("Hello, World!\n"); // 8ab686eafeb1f44702738c8b0f24f2567c36da6d
    $objectRepository->save($blob);

    $index = GitIndex::new();
    $index->addEntry(
        IndexEntry::new(
            FileStatFactory::default(),
            ObjectHash::parse('8ab686eafeb1f44702738c8b0f24f2567c36da6d'),
            TrackedPath::parse('README.md')
        )
    );
    $indexRepository = new IndexRepository;
    $indexRepository->save($index);

    CommandRunner::run('php git commit -m "first commit"');
});

describe('diff-index', function () {
    it(
        'outputs diff results',
        function () {
            file_put_contents(F_GIT_TRACKING_ROOT . '/README.md', "Hello, World!\nI'm sumire!\n");

            $tester = new CommandTester(new DiffIndexCommand);
            $tester->execute([
                'tree-ish' => 'HEAD',
                '--cached' => false,
                '--stat' => false,
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe(":100644 100644 8ab686eafeb1f44702738c8b0f24f2567c36da6d 0000000000000000000000000000000000000000 M\tREADME.md\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );

    it(
        'outputs diff results on stat',
        function () {
            file_put_contents(F_GIT_TRACKING_ROOT . '/README.md', "Hello, World!\nI'm sumire!\n");

            $tester = new CommandTester(new DiffIndexCommand);
            $tester->execute([
                'tree-ish' => 'HEAD',
                '--cached' => false,
                '--stat' => true,
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toContain(
                'README.md',
                '1 files changed, 1 insertions(+), 0 deletions(-)'
            );
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
