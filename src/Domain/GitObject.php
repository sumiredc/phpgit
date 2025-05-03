<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use Error;
use Phpgit\Domain\ObjectType;
use RuntimeException;
use TypeError;
use ValueError;

abstract class GitObject
{
    public ObjectType $objectType {
        get => $this->header->objectType;
    }

    public int $size {
        get => $this->header->size;
    }

    public string $data {
        get => sprintf('%s%s', $this->header->raw, $this->body);
    }

    protected function __construct(
        public readonly GitObjectHeader $header,
        public string $body,
    ) {}

    /** @throws RuntimeException */
    public static function parse(string $uncompressed): self
    {
        [$header, $body] = self::parseToHeaderAndBody($uncompressed);

        if (static::class !== GitObject::class) {
            return new static($header, $body);
        }

        return match ($header->objectType) {
            ObjectType::Blob => new BlobObject($header, $body),
            ObjectType::Tree => new TreeObject($header, $body),
            ObjectType::Commit => new CommitObject($header, $body),
            ObjectType::Tag => throw new Error('NOT SUPPORT OBJECT'), // TODO

            // NOTE: not reachable unless ObjectType is extended
            default => throw new RuntimeException(
                sprintf('failed to initialize object, because not support: %s', $header->objectType->value)
            )
        };
    }

    /** 
     * @return array{0:GitObjectHeader,1:string} [header, body]
     * @throws RuntimeException
     * @throws ValueError
     * @throws TypeError 
     */
    protected static function parseToHeaderAndBody(string $uncompressed): array
    {
        $partition = explode("\0", $uncompressed, 2);
        $header = $partition[0] ?? null;
        $body = $partition[1] ?? null;

        if (empty($header) || is_null($body)) {
            throw new RuntimeException(
                sprintf('failed to parse GitObject: header: %s, body: %s', $header, $body)
            );
        }

        $meta = explode(' ', $header);
        $type = $meta[0] ?? null;
        $sizeStr = $meta[1] ?? null;
        if (empty($type) || is_null($sizeStr) || $sizeStr === '') {
            throw new RuntimeException(
                sprintf('failed to parse GitObject: type: %s, size: %s', $type, $sizeStr)
            );
        }

        $size = intval($sizeStr);
        if (strval($size) !== $sizeStr) {
            throw new TypeError(sprintf('size don\'t be number: %s', $sizeStr));
        }

        $objectType = ObjectType::from($type);

        return [
            GitObjectHeader::new($objectType, $size),
            $body
        ];
    }
}
