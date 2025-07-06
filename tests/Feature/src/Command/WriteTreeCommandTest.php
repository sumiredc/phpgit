<?php

declare(strict_types=1);

use Phpgit\Command\WriteTreeCommand;
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
});

describe('update-index', function () {
    it(
        'returns exit code success',
        function () {
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

            $tester = new CommandTester(new WriteTreeCommand);
            $tester->execute([]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("42986d465a13fdda7fc0f28b0eefd0f002f31aa8\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
