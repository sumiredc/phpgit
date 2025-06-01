<?php

declare(strict_types=1);

use Phpgit\Infra\Repository\GitResourceRepository;
use Tests\CommandRunner;

beforeAll(function () {
    CommandRunner::run('php git init');
});

describe('existsGitDir', function () {
    afterEach(function () {
        CommandRunner::run('php git init');
    });

    it(
        'returns true on exists git dir',
        function () {
            $repository = new GitResourceRepository;

            expect($repository->existsGitDir())->toBeTrue();
        }
    );

    it(
        'returns false on exists git dir',
        function () {
            refreshGit();
            $repository = new GitResourceRepository;

            expect($repository->existsGitDir())->toBeFalse();
        }
    );
});

describe('makeGitObjectDir', function () {
    beforeEach(function () {
        set_error_handler(fn() => true);
    });

    afterEach(function () {
        restore_error_handler();
        CommandRunner::run('php git init');
    });

    it(
        'creates git object directory',
        function () {
            refreshGit();
            $repository = new GitResourceRepository;

            expect(is_dir(F_GIT_OBJECTS_DIR))->toBeFalse();

            $repository->makeGitObjectDir();
            expect(is_dir(F_GIT_OBJECTS_DIR))->toBeTrue();
        }
    );

    it(
        'throws an exception on fails to creats directory',
        function (Throwable $exception) {
            $repository = new GitResourceRepository;

            expect(fn() => $repository->makeGitObjectDir())->toThrow($exception);
        }
    )
        ->with([
            [new RuntimeException('failed to mkdir: /tmp/project/.git/objects')]
        ]);
});

describe('makeGitHeadsDir', function () {
    beforeEach(function () {
        set_error_handler(fn() => true);
    });

    afterEach(function () {
        restore_error_handler();
        CommandRunner::run('php git init');
    });

    it(
        'creates git heads directory',
        function () {
            refreshGit();
            $repository = new GitResourceRepository;

            expect(is_dir(F_GIT_REFS_HEADS_DIR))->toBeFalse();

            $repository->makeGitHeadsDir();
            expect(is_dir(F_GIT_REFS_HEADS_DIR))->toBeTrue();
        }
    );

    it(
        'throws an exception on fails to creates directory',
        function (Throwable $exception) {
            $repository = new GitResourceRepository;

            expect(fn() => $repository->makeGitHeadsDir())->toThrow($exception);
        }
    )
        ->with([
            [new RuntimeException('failed to mkdir: /tmp/project/.git/refs/heads')]
        ]);
});

describe('createGitHead', function () {
    beforeEach(function () {
        set_error_handler(fn() => true);
    });

    afterEach(function () {
        restore_error_handler();
        CommandRunner::run('php git init');
    });

    it(
        'creates git head file',
        function () {
            $repository = new GitResourceRepository;

            expect(file_exists(F_GIT_HEAD))->toBeFalse();

            $repository->createGitHead();
            expect(file_exists(F_GIT_HEAD))->toBeTrue();
        }
    );

    it(
        'throws an exception on failes to creates',
        function (Throwable $expected) {
            refreshGit();
            $repository = new GitResourceRepository;

            expect(fn() =>  $repository->createGitHead())->toThrow($expected);
        }
    )
        ->with([
            [new RuntimeException('failed to touch: /tmp/project/.git/HEAD')]
        ]);
});


describe('saveGitHead', function () {
    beforeEach(function () {
        set_error_handler(fn() => true);
    });

    afterEach(function () {
        restore_error_handler();
        CommandRunner::run('php git init');
    });

    it(
        'match to head contents to arg branch',
        function (string $branch, string $expected) {
            $repository = new GitResourceRepository;

            $contents = file_get_contents(F_GIT_HEAD);
            expect($contents)->not()->toBe($expected);

            $repository->saveGitHead($branch);
            $contents = file_get_contents(F_GIT_HEAD);
            expect($contents)->toBe($expected);
        }
    )
        ->with([
            ['develop', 'ref: refs/heads/develop'],
            ['stage', 'ref: refs/heads/stage']
        ]);

    it(
        'throws an exception on failes to save',
        function (Throwable $expected) {
            refreshGit();
            $repository = new GitResourceRepository;

            expect(fn() =>  $repository->saveGitHead('dummy-branch'))->toThrow($expected);
        }
    )
        ->with([
            [new RuntimeException('failed to write in file: /tmp/project/.git/HEAD')]
        ]);
});
