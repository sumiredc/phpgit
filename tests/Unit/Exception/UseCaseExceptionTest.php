<?php

declare(strict_types=1);

use Phpgit\Exception\UseCaseException;

describe('__construct', function () {
    it(
        'initializes then the message be formatted',
        function (string $format, array $args, string $expected) {
            $actual = new UseCaseException($format, ...$args);

            expect($actual->getMessage())->toBe($expected);
        }
    )
        ->with([
            'one arg' => [
                'sample %s text',
                ['dummy'],
                'sample dummy text'
            ],
            'two args' => [
                'sample %s text version %d',
                ['error', 3],
                'sample error text version 3'
            ],
            'three args' => [
                'sample %s text version %d.%d',
                ['formatted', 2, 5],
                'sample formatted text version 2.5'
            ],
        ]);
});
