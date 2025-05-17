<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Symfony\Component\Console\Input\InputInterface;

readonly final class HashObjectRequest
{
    private function __construct(
        public readonly string $file,
    ) {}

    public static function new(InputInterface $input): self
    {
        $file = strval($input->getArgument('file'));

        return new self($file);
    }
}
