<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Symfony\Component\Console\Input\InputInterface;

readonly final class GitCommitTreeRequest
{
    private function __construct(
        public readonly string $tree,
        public readonly string $message,
    ) {}

    public static function new(InputInterface $input): self
    {
        $tree = strval($input->getArgument('tree'));
        $message = strval($input->getOption('message'));

        return new self($tree, $message);
    }
}
