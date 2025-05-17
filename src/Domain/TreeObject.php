<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use Phpgit\Domain\ObjectType;
use UnexpectedValueException;

final class TreeObject extends GitObject
{
    private const MODE_LENGTH = 6;
    private const SHA1_BLOB_LENGTH = 20;

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
        ObjectHash $hash,
        string $objectName
    ): void {
        $this->body .= sprintf("%s %s\0%s", $mode->value, $objectName, hex2bin($hash->value));
        $this->size = strlen($this->body);
    }

    public function prettyPrint(): string
    {
        $offset = 0;
        $contents = '';

        while ($offset < $this->size) {
            $gitFileMode = $this->parseModeAndAdvanceOffset($offset);
            $objectType = $gitFileMode->toObjectType();
            $objectName = $this->parseObjectNameAndAdvanceOffset($offset);
            $objectHash = $this->parseObjectHashAndAdvanceOffset($offset);

            $contents .= sprintf(
                "%s %s %s\t%s\n",
                $gitFileMode->value,
                $objectType->value,
                $objectHash->value,
                $objectName,
            );
        }

        return $contents;
    }

    private function parseModeAndAdvanceOffset(int &$offset): GitFileMode
    {
        $mode = substr($this->body, $offset, self::MODE_LENGTH);
        $offset += self::MODE_LENGTH + 1; // overwrite, 1 = space

        return GitFileMode::from($mode);
    }

    private function parseObjectNameAndAdvanceOffset(int &$offset): string
    {
        $index = $offset;
        $objectName = '';

        while ($this->body[$index] !== "\0") {
            $objectName .= $this->body[$index];
            $index++;
        }

        $offset = $index + 1; // overwrite, 1 = Null-terminated string

        return $objectName;
    }

    private function parseObjectHashAndAdvanceOffset(int &$offset): ObjectHash
    {
        $hash = bin2hex(substr($this->body, $offset, self::SHA1_BLOB_LENGTH));
        $offset += self::SHA1_BLOB_LENGTH;

        return ObjectHash::parse($hash);
    }
}
