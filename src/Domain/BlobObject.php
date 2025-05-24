<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use Phpgit\Domain\ObjectType;
use UnexpectedValueException;

final class BlobObject extends GitObject
{
    protected function __construct(GitObjectHeader $header, string $body)
    {
        if ($header->objectType !== ObjectType::Blob) {
            throw new UnexpectedValueException(
                sprintf('unexpected ObjectType value: %s', $header->objectType->value)
            );
        }

        parent::__construct($header, $body);
    }

    public static function new(string $contents): self
    {
        $type = ObjectType::Blob;
        $size = strlen($contents);
        $header = GitObjectHeader::new($type, $size);

        return new self($header, $contents);
    }

    public function prettyPrint(): string
    {
        return $this->body;
    }
}
