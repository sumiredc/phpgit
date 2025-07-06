<?php

declare(strict_types=1);

use Phpgit\Command\LsFilesCommand;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\TrackedPath;
use Phpgit\Infra\Repository\IndexRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\CommandRunner;
use Tests\Factory\FileStatFactory;

beforeAll(function () {
    CommandRunner::run('php git init');

    $index = GitIndex::new();
    $index->addEntry(
        IndexEntry::new(
            FileStatFactory::default(),
            ObjectHash::parse('8ab686eafeb1f44702738c8b0f24f2567c36da6d'),
            TrackedPath::parse('README.md')
        ),
    );
    $index->addEntry(
        IndexEntry::new(
            FileStatFactory::default(),
            ObjectHash::parse('ca86eaa41779c51dc1584207c5b3e922b3e7732e'),
            TrackedPath::parse('main.go')
        )
    );
    $indexRepository = new IndexRepository;
    $indexRepository->save($index);
});

describe('ls-files', function () {
    it(
        'outputs files list in index',
        function () {
            $tester = new CommandTester(new LsFilesCommand);
            $tester->execute([]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("README.md\nmain.go\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );

    it(
        'outputs files list with tag in index',
        function () {
            $tester = new CommandTester(new LsFilesCommand);
            $tester->execute([
                '--tag' => true
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("H README.md\nH main.go\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );

    it(
        'outputs files list separate zero in index',
        function () {
            ob_start();

            $tester = new CommandTester(new LsFilesCommand);
            $tester->execute([
                '--zero' => true
            ]);

            $output = ob_get_clean();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("README.md\0main.go\0");
            expect($exitCode)->toBeExitSuccess();
        }
    );

    it(
        'outputs files list with stage details in index',
        function () {
            $tester = new CommandTester(new LsFilesCommand);
            $tester->execute([
                '--stage' => true
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("100644 8ab686eafeb1f44702738c8b0f24f2567c36da6d 0\tREADME.md\n100644 ca86eaa41779c51dc1584207c5b3e922b3e7732e 0\tmain.go\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );

    it(
        'outputs files list with debug details in index',
        function () {
            $tester = new CommandTester(new LsFilesCommand);
            $tester->execute([
                '--debug' => true
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe("README.md\n  ctime: 1745070011:0\n  mtime: 1744383756:0\n  dev: 16777232\tino: 63467197\n  uid: 501\tgid: 20\n  size: 53\tflags: 0\nmain.go\n  ctime: 1745070011:0\n  mtime: 1744383756:0\n  dev: 16777232\tino: 63467197\n  uid: 501\tgid: 20\n  size: 53\tflags: 0\n");
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
