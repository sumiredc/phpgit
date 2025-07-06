<?php

declare(strict_types=1);

use Phpgit\Command\UpdateRefCommand;
use Phpgit\Domain\GitFileMode;
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
    $objectRepository->save($tree); // 3255d8928e457470d437e1940b826eec74285e90
});

describe('update-index', function () {
    it(
        'returns exit code success',
        function () {
            $tester = new CommandTester(new UpdateRefCommand);
            $tester->execute([
                'ref' => 'HEAD',
                'arg1' => '3255d8928e457470d437e1940b826eec74285e90'
            ]);

            $output = $tester->getDisplay();
            $exitCode = $tester->getStatusCode();

            expect($output)->toBe('');
            expect($exitCode)->toBeExitSuccess();
        }
    );
});
