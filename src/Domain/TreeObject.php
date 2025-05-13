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
        $end = $offset + 6;
        $mode = substr($this->body, $offset, $end);
        $offset = $end; // overwrite

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

        $offset = $index + 1; // overwrite, next Null-terminated string

        return $objectName;
    }

    private function parseObjectHashAndAdvanceOffset(int &$offset): ObjectHash
    {
        $end = $offset + 20;
        $hash = bin2hex(substr($this->body, $offset, $end));
        $offset = $end;

        return ObjectHash::parse($hash);
    }
}
