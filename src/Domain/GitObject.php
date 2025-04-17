<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use Phpgit\Domain\ObjectType;
use RuntimeException;
use TypeError;
use ValueError;

abstract class GitObject
{
    public ObjectType $objectType {
        get => $this->header->objectType;
    }

    protected function __construct(
        public readonly GitObjectHeader $header,
        public string $body,
    ) {}

    /** @throws RuntimeException */
    public static function parse(string $uncompressed): self
    {
        [$header, $body] = self::parseToHeaderAndBody($uncompressed);

        return match ($header->objectType) {
            ObjectType::Tree => new TreeObject($header, $body),
            ObjectType::Blob => new BlobObject($header, $body),
            default => throw new RuntimeException(
                sprintf('don\'t support for object type: %s', $header->objectType->value)
            ),
        };
    }

    /** 
     * @return array{0:GitObjectHeader,1:string} [header, body]
     * @throws RuntimeException|ValueError|TypeError 
     */
    protected static function parseToHeaderAndBody(string $uncompressed): array
    {
        [$header, $body] = explode("\0", $uncompressed, 2);

        if (empty($header) || empty($body)) {
            throw new RuntimeException(
                sprintf('failed to parse BlobObject: header: %s, body: %s', $body, $header)
            );
        }

        [$type, $size] = explode(' ', $header);
        if (empty($type) || is_null($size) || $size === '') {
            throw new RuntimeException(
                sprintf('failed to parse BlobObject: type: %s, size: %s', $type, $size)
            );
        }

        $objectType = ObjectType::from($type);

        return [GitObjectHeader::new($objectType, intval($size)), $body];
    }

    final public function data(): string
    {
        return sprintf('%s%s', $this->header->raw, $this->body);
    }
}
