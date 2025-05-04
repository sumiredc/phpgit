<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use UnexpectedValueException;

final class CommitObject extends GitObject
{
    protected function __construct(
        GitObjectHeader $header,
        string $body,
    ) {
        if ($header->objectType !== ObjectType::Commit) {
            throw new UnexpectedValueException(
                sprintf('unexpected ObjectType value: %s', $header->objectType->value)
            );
        }

        parent::__construct($header, $body);
    }

    public static function new(
        ObjectHash $treeHash,
        GitSignature $author,
        GitSignature $committer,
        string $message
    ): self {
        $type = ObjectType::Commit;
        $content = sprintf("%s %s\n", ObjectType::Tree->value, $treeHash->value)
            . sprintf("author %s\n", $author->toRawString())
            . sprintf("committer %s\n", $committer->toRawString())
            . "\n"
            . $message
            . "\n";

        $size = strlen($content);
        $header = GitObjectHeader::new($type, $size);

        return new self($header, $content);
    }
}
