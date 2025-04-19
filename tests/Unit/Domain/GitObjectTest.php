<?php

declare(strict_types=1);

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\TreeObject;

describe('parse', function () {
    it('should initializes object', function (
        string $object,
        string $objectClass,
        ObjectType $objectType,
        int $size,
        string $body,
    ) {
        $actual = GitObject::parse($object);

        expect($actual::class)->toBe($objectClass);
        expect($actual->objectType)->toBe($objectType);
        expect($actual->size)->toBe($size);
        expect($actual->body)->toBe($body);
        expect($actual->data)->toBe($object);
    })
        ->with([
            'blob' => [
                "blob 73\0" . 'package main

import "fmt"

func main() {
	fmt.Println("Hello, world")
}
',
                BlobObject::class,
                ObjectType::Blob,
                73,
                'package main

import "fmt"

func main() {
	fmt.Println("Hello, world")
}
'
            ],
            'tree' => [
                "tree 67\0" . '100644 blob 59e0b395a6ee16f4673442df6f59d4be1f0daea6	README.md
040000 tree 59e0b395a6ee16f4673442df6f59d4be1f0daea6	src
',
                TreeObject::class,
                ObjectType::Tree,
                67,
                '100644 blob 59e0b395a6ee16f4673442df6f59d4be1f0daea6	README.md
040000 tree 59e0b395a6ee16f4673442df6f59d4be1f0daea6	src
'
            ],
        ]);

    it('fails to parse object, throws RuntimeException', function (string $object) {
        GitObject::parse($object);
    })
        ->with([
            'no header' => ["\0" . 'body string'],
            'no null-terminated string, because unescaped' => ['blob 11\0body string'],
            'cannot get type' => ["tree0\0"],
            'cannot get size, is null' => ["blob\0"],
            'cannot get size, is empty string' => ["tree \0"],

        ])
        ->throws(RuntimeException::class);

    it('fails to parse object, throws TypeError', function (string $object) {
        GitObject::parse($object);
    })
        ->with([
            'size is not number' => ["blob a\0a"],
        ])
        ->throws(TypeError::class);

    it('fails to parse object, throws ValueError', function (string $object) {
        GitObject::parse($object);
    })
        ->with([
            'not allowed ObjectType' => ["image 0\0"]
        ])
        ->throws(ValueError::class);
});
