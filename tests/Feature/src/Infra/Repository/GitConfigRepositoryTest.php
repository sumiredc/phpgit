<?php

declare(strict_types=1);

use Phpgit\Infra\Repository\GitConfigRepository;
use Tests\CommandRunner;

beforeAll(function () {
    CommandRunner::run('php git init');
});

describe('create', function () {
    beforeEach(function () {
        @unlink(F_GIT_CONFIG);
    });

    it(
        'creates a config file',
        function () {
            $repository = new GitConfigRepository;
            $repository->create();

            expect(true)->toBeTrue();
            expect(file_exists(F_GIT_CONFIG));
        }
    );
});

describe('get', function () {
    beforeEach(function () {
        @unlink(F_GIT_CONFIG);

        $repository = new GitConfigRepository;
        $repository->create();
    });

    it(
        'parses to config',
        function () {
            $repository = new GitConfigRepository;
            $actual = $repository->get();

            expect($actual->repositoryFormatVersion)->toBe(GIT_REPOSITORY_FORMAT_VERSION);
            expect($actual->filemode)->toBe(GIT_FILEMODE);
            expect($actual->bare)->toBe(GIT_BARE);
            expect($actual->logAllRefUpdates)->toBe(GIT_LOG_ALL_REF_UPDATES);
            expect($actual->ignoreCase)->toBe(GIT_IGNORE_CASE);
            expect($actual->preComposeUnicode)->toBe(GIT_PRE_COMPOSE_UNICODE);
            expect($actual->userName)->toBe(GIT_DEFAULT_USER_NAME);
            expect($actual->userEmail)->toBe(GIT_DEFAULT_USER_EMAIL);
        }
    );

    it(
        'parses to custom user values',
        function (
            string $userName,
            string $userEmail,
            string $expectedUserName,
            string $expectedUserEmail,
        ) {
            file_put_contents(F_GIT_CONFIG, "[user]\n", FILE_APPEND);
            file_put_contents(F_GIT_CONFIG, "        name = $userName\n", FILE_APPEND);
            file_put_contents(F_GIT_CONFIG, "        email = $userEmail\n", FILE_APPEND);

            $repository = new GitConfigRepository;
            $actual = $repository->get();

            expect($actual->userName)->toBe($expectedUserName);
            expect($actual->userEmail)->toBe($expectedUserEmail);
        }
    )
        ->with([
            ['sample.name', 'sample@xxx.xx', 'sample.name', 'sample@xxx.xx']
        ]);

    it(
        'fails to parses config values',
        function (string $removeKey, Throwable $expected) {
            $contents = file_get_contents(F_GIT_CONFIG);
            $contents = str_replace($removeKey, 'xxx', $contents);
            file_put_contents(F_GIT_CONFIG, $contents);

            $repository = new GitConfigRepository;

            expect(fn() => $repository->get())->toThrow($expected);
        }
    )
        ->with([
            [
                'repositoryformatversion',
                new ParseError('failed to parse to core.repositoryformatversion')
            ],
            [
                'filemode',
                new ParseError('failed to parse to core.filemode')
            ],
            [
                'bare',
                new ParseError('failed to parse to core.bare'),
            ],
            [
                'logallrefupdates',
                new ParseError('failed to parse to core.logallrefupdates'),
            ],
            [
                'ignorecase',
                new ParseError('failed to parse to core.ignorecase'),
            ],
            [
                'precomposeunicode',
                new ParseError('failed to parse to core.precomposeunicode'),
            ]
        ]);
});
