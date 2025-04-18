<?php

declare(strict_types=1);

use Phpgit\Domain\BlobObject;

describe('new', function () {
    it('should initialize object', function (string $content, int $size, string $raw) {
        $object = BlobObject::new($content);

        expect($object->body)->toBe($content);
        expect($object->size)->toBe($size);
        expect($object->header->raw)->toBe($raw);
    })
        ->with([
            ['<?php

declare(strict_types=1);

echo \'test\';
', 46, "blob 46\0"],
            ['package main

import "fmt"

func main() {
	fmt.Println("Hello, world")
}
', 73, "blob 73\0"]
        ]);

    it('miss match object type', function (string $content) {
        BlobObject::parse($content);
    })
        ->throws(UnexpectedValueException::class, 'unexpected ObjectType value: tree')
        ->with([
            ["tree 67\0" . "100644 blob 59e0b395a6ee16f4673442df6f59d4be1f0daea6	README.md
040000 tree 59e0b395a6ee16f4673442df6f59d4be1f0daea6	src
"]
        ]);
});
