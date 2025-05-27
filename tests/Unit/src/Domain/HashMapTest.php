<?php

declare(strict_types=1);

use Phpgit\Domain\HashMap;

describe('new', function () {
    it(
        'matches to count to zero on initializes',
        function () {
            $hashMap = HashMap::new();

            expect(count($hashMap))->toBe(0);
        }
    );
});

describe('parse', function () {
    it(
        'match to parse array and iteratable',
        function (array $values, int $iterateCount, array $expected) {
            $counter = Mockery::mock(new class {
                function __invoke() {}
            });
            $counter->shouldReceive('__invoke')->times($iterateCount);

            $hashMap = HashMap::parse($values);

            expect(count($hashMap))->toBe(count($expected));

            foreach ($hashMap as $key => $value) {
                $counter();
                expect($value)->toBe($expected[$key]);
            }
        }
    )
        ->with([
            'array is empty' => [
                [],
                0,
                []
            ],
            'array is three length' => [
                [
                    'one' => 'one value',
                    'two' => 'two value',
                    'three' => 'three value',
                ],
                3,
                [
                    'one' => 'one value',
                    'two' => 'two value',
                    'three' => 'three value',
                ],
            ],
            'array is hundred length' => function () {
                $values = [];
                for ($i = 0; $i < 100; $i++) {
                    $values[str_pad(strval($i), 5, '0', STR_PAD_LEFT)] = $i;
                }

                return [$values, 100, $values];
            },
        ]);
});


describe('get, set, delete, exists', function () {
    dataset('hashmap100', function () {
        $hashMap = HashMap::new();
        for ($i = 0; $i < 100; $i++) {
            $hashMap->set(
                str_pad(strval($i), 5, '0', STR_PAD_LEFT),
                $i
            );
        }

        return [$hashMap];
    });

    it(
        'operate correctly get',
        function (HashMap $hashMap) {
            expect($hashMap->get('0000'))->toBeNull();
            expect($hashMap->get('00001'))->toBe(1);
            expect($hashMap->get('00015'))->toBe(15);
            expect($hashMap->get('00036'))->toBe(36);
            expect($hashMap->get('00093'))->toBe(93);
            expect($hashMap->get('00100'))->toBeNull();
        }
    )
        ->with('hashmap100');

    it(
        'operate correctly set',
        function (HashMap $hashMap) {
            $hashMap->set('0000', 0);
            $hashMap->set('00001', 'new01');
            $hashMap->set('00015', 'new15');
            $hashMap->set('00036', 'new36');
            $hashMap->set('00093', 'new93');
            $hashMap->set('00100', 100);

            expect($hashMap->get('0000'))->toBe(0);
            expect($hashMap->get('00001'))->toBe('new01');
            expect($hashMap->get('00015'))->toBe('new15');
            expect($hashMap->get('00036'))->toBe('new36');
            expect($hashMap->get('00093'))->toBe('new93');
            expect($hashMap->get('00100'))->toBe(100);
        }
    )
        ->with('hashmap100');

    it(
        'operate correctly delete',
        function (HashMap $hashMap) {
            $hashMap->delete('0000');
            $hashMap->delete('00001');
            $hashMap->delete('00015');
            $hashMap->delete('00036');
            $hashMap->delete('00093');
            $hashMap->delete('00100');

            expect($hashMap->get('0000'))->toBeNull();
            expect($hashMap->get('00001'))->toBeNull();
            expect($hashMap->get('00015'))->toBeNull();
            expect($hashMap->get('00036'))->toBeNull();
            expect($hashMap->get('00093'))->toBeNull();
            expect($hashMap->get('00100'))->toBeNull();
        }
    )
        ->with('hashmap100');

    it(
        'operate correctly exists',
        function (HashMap $hashMap) {
            expect($hashMap->exists('0000'))->toBeFalse();
            expect($hashMap->exists('00001'))->toBeTrue();
            expect($hashMap->exists('00015'))->toBeTrue();
            expect($hashMap->exists('00036'))->toBeTrue();
            expect($hashMap->exists('00093'))->toBeTrue();
            expect($hashMap->exists('00100'))->toBeFalse();
        }
    )
        ->with('hashmap100');
});

describe('failes case', function () {
    it(
        'throws an exception on key is not string when calls parse() method',
        function (array $values, Throwable $expected) {
            expect(fn() => HashMap::parse($values))->toThrow($expected);
        }
    )
        ->with([
            [
                ['value1', 'value2', 'value3'],
                new InvalidArgumentException('key is not string: 0 is type integer')
            ]
        ]);

    it(
        'throws an exception on value is null when calls parse() method',
        function (array $values, Throwable $expected) {
            expect(fn() => HashMap::parse($values))->toThrow($expected);
        }
    )
        ->with([
            [
                ['key1' => 1, 'key2' => null, 'key3' => 3],
                new InvalidArgumentException('null value is not allowed: key2')
            ]
        ]);

    it(
        'throws an exception on value is null when calls set() method',
        function (Throwable $expected) {
            $hashMap = HashMap::new();
            expect(fn() => $hashMap->set('key', null))->toThrow($expected);
        }
    )
        ->with([
            [new InvalidArgumentException('null value is not allowed')]
        ]);
});
