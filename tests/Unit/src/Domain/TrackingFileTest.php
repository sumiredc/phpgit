<?php

declare(strict_types=1);

use Phpgit\Domain\TrackingPath;

describe('new', function () {
    it('should match arg to property', function (string $path) {
        $actual = TrackingPath::new($path);

        expect($actual->value)->toBe($path);
    })
        ->with([
            ['README.md'],
            ['src/main.go'],
            ['src/user/http/handler.go'],
        ]);
});

describe('fullPath', function () {
    it('return to fullPath', function (string $path, string $expected) {
        $actual = TrackingPath::new($path);

        expect($actual->fullPath())->toBe($expected);
    })
        ->with([
            ['README.md', F_GIT_TRACKING_ROOT . '/README.md'],
            ['src/main.go', F_GIT_TRACKING_ROOT . '/src/main.go'],
            ['src/user/http/handler.go', F_GIT_TRACKING_ROOT . '/src/user/http/handler.go'],
        ]);
});
