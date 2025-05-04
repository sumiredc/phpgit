<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Domain\CommandInput\GitLsFilesOptionAction;
use Symfony\Component\Console\Input\InputInterface;

readonly final class GitLsFilesRequest
{
    private function __construct(
        public readonly GitLsFilesOptionAction $action,
    ) {}

    public static function new(InputInterface $input): self
    {
        $tag = boolval($input->getOption('tag'));
        $zero = boolval($input->getOption('zero'));
        $stage = boolval($input->getOption('stage'));
        $debug = boolval($input->getOption('debug'));

        $action = match (true) {
            $tag => GitLsFilesOptionAction::Tag,
            $zero => GitLsFilesOptionAction::Zero,
            $stage => GitLsFilesOptionAction::Stage,
            $debug => GitLsFilesOptionAction::Debug,
            default => GitLsFilesOptionAction::Default,
        };

        return new self($action);
    }
}
