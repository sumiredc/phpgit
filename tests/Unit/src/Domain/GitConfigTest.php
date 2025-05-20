<?php

declare(strict_types=1);

use Phpgit\Domain\GitConfig;

describe('new', function () {
    it('should match to properties to args', function (
        int $repositoryFormatVersion,
        bool $filemode,
        bool $bare,
        bool $logAllRefUpdates,
        bool $ignoreCase,
        bool $preComposeUnicode,
        string $userName,
        string $userEmail,
    ) {
        $actual = GitConfig::new(
            $repositoryFormatVersion,
            $filemode,
            $bare,
            $logAllRefUpdates,
            $ignoreCase,
            $preComposeUnicode,
            $userName,
            $userEmail,
        );

        expect($actual->repositoryFormatVersion)->toBe($repositoryFormatVersion);
        expect($actual->filemode)->toBe($filemode);
        expect($actual->bare)->toBe($bare);
        expect($actual->logAllRefUpdates)->toBe($logAllRefUpdates);
        expect($actual->ignoreCase)->toBe($ignoreCase);
        expect($actual->preComposeUnicode)->toBe($preComposeUnicode);
        expect($actual->userName)->toBe($userName);
        expect($actual->userEmail)->toBe($userEmail);
    })
        ->with([
            'true case' => [1, true, true, true, true, true, 'sumire', 'sumire@example.com'],
            'false case' => [0, false, false, false, false, false, 'muhoho', 'muhoho@example.com'],
        ]);

    it(
        'fails to set to repositoryFormatVersion when set invalid value',
        function (int $repositoryFormatVersion, Throwable $expected) {
            expect(fn() => GitConfig::new(
                $repositoryFormatVersion,
                true,
                true,
                true,
                true,
                true,
                'user name',
                'email@example.com',
            ))
                ->toThrow($expected);
        }
    )
        ->with([
            [-1, new InvalidArgumentException('invalid value: -1')],
            [2, new InvalidArgumentException('invalid value: 2')],
        ]);
});
