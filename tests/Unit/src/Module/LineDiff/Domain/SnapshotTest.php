<?php

declare(strict_types=1);

use Phpgit\Module\LineDiff\Domain\Snapshot;

describe('Snapshot', function () {
    it('saves and retrieves values correctly', function () {
        $snapshot = new Snapshot();

        $snapshot->save(0, 10);
        $snapshot->save(1, 20);

        expect($snapshot->get(0))->toBe(10);
        expect($snapshot->get(1))->toBe(20);
    });

    it('overwrites existing values with same key', function () {
        $snapshot = new Snapshot();

        $snapshot->save(2, 30);
        expect($snapshot->get(2))->toBe(30);

        $snapshot->save(2, 40);

        expect($snapshot->get(2))->toBe(40);
    });

    it('throws OutOfBoundsException when key does not exist', function () {
        $snapshot = new Snapshot();

        expect(fn() => $snapshot->get(99))->toThrow(new OutOfBoundsException('Key 99 does not exist'));
    });
});
