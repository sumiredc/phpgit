<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Domain\CommandInput\GitUpdateIndexOptionAction;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\ObjectHash;
use Phpgit\Lib\IO;
use Phpgit\Repository\FileRepository;
use Phpgit\Repository\IndexRepository;
use Phpgit\Repository\ObjectRepository;
use Phpgit\UseCase\GitUpdateIndexUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ValueError;

/** @see https://git-scm.com/docs/git-update-index */
#[AsCommand(
    name: 'git:update-index',
    description: 'Register file contents in the working tree to the index',
)]
final class GitUpdateIndexCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'add',
                null,
                InputOption::VALUE_NONE,
                'If a specified file isn’t in the index already then it’s added. Default behaviour is to ignore new files.'
            )
            ->addOption(
                'remove',
                null,
                InputOption::VALUE_NONE,
                'If a specified file is in the index but is missing then it’s removed. Default behavior is to ignore removed files.'
            )
            ->addOption(
                'force-remove',
                null,
                InputOption::VALUE_NONE,
                'Remove the file from the index even when the working directory still has such a file.'
            )
            ->addOption(
                'cacheinfo',
                null,
                InputOption::VALUE_NONE,
                'Directly insert the specified info into the index.'
            )
            ->addArgument(
                'mode',
                InputArgument::OPTIONAL,
                '[only and required by --cacheinfo] Rewrite to file mode. ex: 10755, 10644'
            )
            ->addArgument(
                'object',
                InputArgument::OPTIONAL,
                '[only and required by --cacheinfo] Rewrite to object hash(sha1).'
            )
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                '[required] Files to act on. Note that files beginning with.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new IO($input, $output);
        $objectRepository = new ObjectRepository;
        $fileRepository = new FileRepository;
        $indexRepository = new IndexRepository;
        $useCase = new GitUpdateIndexUseCase(
            $io,
            $objectRepository,
            $fileRepository,
            $indexRepository
        );

        $action = $this->validateOptionAction($input);
        [$file, $mode, $object] = $this->validateArguments($input, $action);

        if (is_null($action)) {
            $io->warning("missing required action option");

            return self::INVALID;
        }

        $result = $useCase($action, $file, $mode, $object);

        return $result->value;
    }

    private function validateOptionAction(InputInterface $input): ?GitUpdateIndexOptionAction
    {
        $add = boolval($input->getOption('add'));
        $remove = boolval($input->getOption('remove'));
        $forceRemove = boolval($input->getOption('force-remove'));
        $cacheinfo = boolval($input->getOption('cacheinfo'));

        return match (true) {
            $add => GitUpdateIndexOptionAction::Add,
            $remove => GitUpdateIndexOptionAction::Remove,
            $forceRemove => GitUpdateIndexOptionAction::ForceRemove,
            $cacheinfo => GitUpdateIndexOptionAction::Cacheinfo,
            default => throw new InvalidOptionException('Not enough options'),
        };
    }

    /** 
     * return to [file, mode, object]
     * 
     * @return array{
     *  0: string,
     *  1: ?GitFileMode,
     *  2: ?ObjectHash,
     * }
     */
    private function validateArguments(
        InputInterface $input,
        GitUpdateIndexOptionAction $action
    ): array {
        switch ($action) {
            case GitUpdateIndexOptionAction::Cacheinfo:
                $mode = $this->requiredValidateArgument($input, 'mode', 'mode');
                $object = $this->requiredValidateArgument($input, 'object', 'object');
                $file = $this->requiredValidateArgument($input, 'file', 'file');

                try {
                    return [
                        $file,
                        GitFileMode::from($mode),
                        ObjectHash::parse($object)
                    ];
                } catch (ValueError) {
                    throw new InvalidArgumentException('Not enough arguments (missing: "mode").');
                } catch (InvalidArgumentException) {
                    throw new InvalidArgumentException('Not enough arguments (missing: "object").');
                }

            default:
                // NOTE: first argument is "file"
                $file = $this->requiredValidateArgument($input, 'mode', 'file');

                return [$file, null, null];
        }
    }

    /** @throws InvalidArgumentException */
    private function requiredValidateArgument(InputInterface $input, string $argName, string $displayName): string
    {
        $value = $input->getArgument($argName);
        if (is_null($value)) {
            throw new InvalidArgumentException(sprintf('Not enough arguments (missing: "%s").', $displayName));
        }

        return strval($value);
    }
}
