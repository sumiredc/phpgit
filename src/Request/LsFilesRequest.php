<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Domain\CommandInput\LsFilesOptionAction;
use Symfony\Component\Console\Input\InputInterface;

readonly final class LsFilesRequest
{
    private function __construct(
        public readonly LsFilesOptionAction $action,
    ) {}

    public static function new(InputInterface $input): self
    {
        $tag = boolval($input->getOption('tag'));
        $zero = boolval($input->getOption('zero'));
        $stage = boolval($input->getOption('stage'));
        $debug = boolval($input->getOption('debug'));

        $action = match (true) {
            $tag => LsFilesOptionAction::Tag,
            $zero => LsFilesOptionAction::Zero,
            $stage => LsFilesOptionAction::Stage,
            $debug => LsFilesOptionAction::Debug,
            default => LsFilesOptionAction::Default,
        };

        return new self($action);
    }
}
