<?php

declare(strict_types=1);

use Phpgit\Domain\DiffStat;

describe('new', function () {
    it(
        'match to args to properties',
        function (string $path) {
            $actual = DiffStat::new($path);

            expect($actual->path)->toBe($path);
            expect($actual->insertions)->toBe(0);
            expect($actual->deletions)->toBe(0);
            expect($actual->total)->toBe(0);
            expect($actual->isChanged())->toBeFalse();
        }
    )
        ->with([
            ['sample/path'],
        ]);
});

describe('insert', function () {
    it('increments to insertions', function () {
        $actual = DiffStat::new('dummy');

        expect($actual->insertions)->toBe(0);
        expect($actual->isChanged())->toBeFalse();

        for ($i = 1; $i <= 10; $i++) {
            $actual->insert();
            expect($actual->insertions)->toBe($i);
            expect($actual->isChanged())->toBeTrue();
        }
    });
});

describe('delete', function () {
    it('increments to deletions', function () {
        $actual = DiffStat::new('dummy');

        expect($actual->deletions)->toBe(0);
        expect($actual->isChanged())->toBeFalse();

        for ($i = 1; $i <= 10; $i++) {
            $actual->delete();
            expect($actual->deletions)->toBe($i);
            expect($actual->isChanged())->toBeTrue();
        }
    });
});

describe('addedFile', function () {
    it(
        'returns to true by isAddedFile on it calls',
        function () {
            $actual = DiffStat::new('dummy');

            expect($actual->isAddedFile())->toBeFalse();
            expect($actual->isChanged())->toBeFalse();

            $actual->addedFile();

            expect($actual->isAddedFile())->toBeTrue();
            expect($actual->isChanged())->toBeTrue();
        }
    );
});

describe('dropedFile', function () {
    it(
        'returns to true by isDropedFile on it calls',
        function () {
            $actual = DiffStat::new('dummy');

            expect($actual->isDropedFile())->toBeFalse();
            expect($actual->isChanged())->toBeFalse();

            $actual->dropedFile();

            expect($actual->isDropedFile())->toBeTrue();
            expect($actual->isChanged())->toBeTrue();
        }
    );
});
