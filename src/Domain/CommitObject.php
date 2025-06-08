<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use UnexpectedValueException;

final class CommitObject extends GitObject
{
    private ?ObjectHash $_treeHash = null;

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
        string $message,
        ?ObjectHash $parentHash,
    ): self {
        $type = ObjectType::Commit;

        $body = sprintf("%s %s\n", ObjectType::Tree->value, $treeHash->value);

        if (!is_null($parentHash)) {
            $body .= sprintf("parent %s\n", $parentHash->value);
        }

        $body .= sprintf("author %s\n", $author->toRawString())
            . sprintf("committer %s\n", $committer->toRawString())
            . "\n"
            . $message
            . "\n";

        $size = strlen($body);
        $header = GitObjectHeader::new($type, $size);

        return new self($header, $body);
    }

    public function prettyPrint(): string
    {
        return $this->body;
    }

    /**
     * @throws UnexpectedValueException
     */
    public function treeHash(): ObjectHash
    {
        if (is_null($this->_treeHash)) {
            $pattern = sprintf('/^%s\s([0-9a-f]{40})\n/', ObjectType::Tree->value);
            preg_match($pattern, $this->body, $matches);

            if (!isset($matches[1])) {
                throw new UnexpectedValueException('Tree hash not found in CommitObject body.');
            }

            $this->_treeHash = ObjectHash::parse($matches[1]);
        }

        return $this->_treeHash;
    }
}
