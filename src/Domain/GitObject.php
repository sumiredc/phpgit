<?php

namespace Phpgit\Domain;

use Phpgit\Domain\ObjectType;

readonly final class GitObject
{
    private function __construct(
        public readonly string $header,
        public readonly string $body,
        public readonly ObjectType $objectType,
        public readonly int $size
    ) {}

    public static function parse(string $uncompressed): ?self
    {
        [$header, $body] = explode('\0', $uncompressed, 2);
        if (empty($header) || empty($body)) {
            return null;
        }

        [$type, $size] = explode(' ', $header);
        if (empty($type) || is_null($size) || $size === '') {
            return null;
        }

        $objectType = ObjectType::tryFrom($type);
        if (is_null($objectType)) {
            return null;
        }

        return new self($header, $body, $objectType, intval($size));
    }
}
