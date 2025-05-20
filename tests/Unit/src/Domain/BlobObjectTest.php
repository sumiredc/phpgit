<?php

declare(strict_types=1);

use Phpgit\Domain\BlobObject;

describe('new', function () {
    it(
        'should initialize object',
        function (string $content, int $size, string $rawHeader, string $data) {
            $object = BlobObject::new($content);

            expect($object->body)->toBe($content);
            expect($object->size)->toBe($size);
            expect($object->header->raw)->toBe($rawHeader);
            expect($object->data)->toBe($data);
        }
    )
        ->with([
            // case 1: PHP
            [
                'content' => '<?php

declare(strict_types=1);

echo \'test\';
',
                'size' =>  46,
                'rawHeader' => "blob 46\0",
                'data' => "blob 46\0" . '<?php

declare(strict_types=1);

echo \'test\';
'
            ],
            // case 2: golang
            [
                'content' => 'package main

import "fmt"

func main() {
	fmt.Println("Hello, world")
}
',
                'size' => 73,
                'rawHeader' => "blob 73\0",
                'data' => "blob 73\0" . 'package main

import "fmt"

func main() {
	fmt.Println("Hello, world")
}
'
            ]
        ]);

    it(
        'miss match object type',
        function (string $content, Throwable $expected) {
            expect(fn() => BlobObject::parse($content))->toThrow($expected);
        }
    )
        ->with([
            [
                "tree 67\0" . '100644 blob 59e0b395a6ee16f4673442df6f59d4be1f0daea6	README.md
040000 tree 59e0b395a6ee16f4673442df6f59d4be1f0daea6	src
',
                new UnexpectedValueException('unexpected ObjectType value: tree')
            ]
        ]);
});
