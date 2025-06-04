<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\FileRepository;
use Phpgit\Request\HashObjectRequest;
use Phpgit\UseCase\HashObjectUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-hash-object */
#[AsCommand(
    name: 'hash-object',
    description: 'Compute object ID and optionally create an object from a file',
)]
final class HashObjectCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        HashObjectRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = HashObjectRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $fileRepository = new FileRepository();
        $useCase = new HashObjectUseCase($printer, $fileRepository);

        $result = $useCase($request);

        return $result->value;
    }
}
