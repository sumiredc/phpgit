<?php

declare(strict_types=1);

use Phpgit\Command\CommitTreeCommand;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\Service\HashPattern;
use Phpgit\Domain\TreeObject;
use Phpgit\Infra\Repository\ObjectRepository;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\CommandRunner;
use Tests\Factory\ObjectHashFactory;

beforeAll(function () {
    CommandRunner::run('php git init');

    $tree = TreeObject::new();
    $tree->appendEntry(GitFileMode::DefaultFile, ObjectHashFactory::new(), 'dummy');
    $objectRepository = new ObjectRepository;
    $objectRepository->save($tree);
});

describe('commit-tree', function () {
    it(
        'outputs commit-tree results',
        function () {
            $tester = new CommandTester(new CommitTreeCommand);
            $tester->execute([
                'tree' => '3255d8928e457470d437e1940b826eec74285e90',
                '--message' => 'first commit',
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect(HashPattern::sha1(str_replace("\n", '', $output)))->toBeTrue();
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
