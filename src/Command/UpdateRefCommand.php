<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Infra\Repository\RefRepository;
use Phpgit\Request\UpdateRefRequest;
use Phpgit\Service\ResolveRevisionService;
use Phpgit\UseCase\UpdateRefUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-update-ref */
#[AsCommand(
    name: 'update-ref',
    description: 'Update the object name stored in a ref safely',
)]
final class UpdateRefCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        UpdateRefRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = UpdateRefRequest::new($input);
        $printer = new CliPrinter($input, $output);
        $objectRepository = new ObjectRepository;
        $refRepository = new RefRepository;

        $useCase = new UpdateRefUseCase(
            $printer,
            $objectRepository,
            $refRepository,
            new ResolveRevisionService($refRepository)
        );

        $result = $useCase($request);

        return $result->value;
    }
}
