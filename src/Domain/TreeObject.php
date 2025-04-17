<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use Phpgit\Domain\ObjectType;
use UnexpectedValueException;

final class TreeObject extends GitObject
{
    protected function __construct(GitObjectHeader $header, string $body)
    {
        if ($header->objectType !== ObjectType::Tree) {
            throw new UnexpectedValueException(
                sprintf('unexpected ObjectType value: %s', $header->objectType->value)
            );
        }

        parent::__construct($header, $body);
    }

    public static function new(): self
    {
        $type = ObjectType::Tree;
        $size = 0;
        $header = GitObjectHeader::new($type, $size);

        return new self($header, '');
    }

    public function appendEntry(
        GitFileMode $mode,
        ObjectType $type,
        ObjectHash $hash,
        string $objectName
    ): void {
        $this->body .= sprintf("%s %s %s\t%s\n", $mode->value, $type->value, $hash->value, $objectName);
    }
}
