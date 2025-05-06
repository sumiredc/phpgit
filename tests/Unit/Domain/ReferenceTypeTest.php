<?php

declare(strict_types=1);

use Phpgit\Domain\ReferenceType;

describe('prefix', function () {
    it(
        'match to prefix',
        function (ReferenceType $refType, string $expected) {
            $actual = $refType->prefix();

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            'local' => [ReferenceType::Local, 'refs/heads'],
            'remote' => [ReferenceType::Remote, 'refs/remotes'],
            'tag' => [ReferenceType::Tag, 'refs/tags'],
            'note' => [ReferenceType::Note, 'refs/notes'],
            'stash' => [ReferenceType::Stash, 'refs/stash'],
            'replace' => [ReferenceType::Replace, 'refs/replace'],
            'bisect' => [ReferenceType::Bisect, 'refs/bisect'],
        ]);
});
