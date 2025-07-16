<?php

declare(strict_types=1);

use Phpgit\Command\CommitCommand;
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
});

describe('commit', function () {
    it(
        'outputs commit results and message',
        function () {
            $tester = new CommandTester(new CommitCommand);
            $tester->execute([
                '--message' => 'first commit',
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toContain(
                'main (root-commit)',
                'first commit',
                '1 files changed, 2 insertions(+), 0 deletions(-)',
                'create mode 100644 README.md'
            );
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
