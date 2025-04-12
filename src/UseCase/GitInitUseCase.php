<?php

namespace Phpgit\UseCase;

use Phpgit\Domain\Branch;
use Phpgit\Domain\Result;
use Phpgit\Exception\FileAlreadyExistsException;
use Phpgit\Lib\IOInterface;
use RuntimeException;
use Throwable;

final class GitInitUseCase
{
    public function __construct(private readonly IOInterface $io) {}

    public function __invoke(): Result
    {
        try {
            if (is_dir(F_GIT_DIR)) {
                throw new FileAlreadyExistsException('a Git repository already exists in this directory');
            }

            if (!mkdir(F_GIT_OBJECTS_DIR, 0755, true)) {
                throw new RuntimeException(sprintf('failed to mkdir: %s', F_GIT_OBJECTS_DIR));
            }

            if (!mkdir(F_GIT_HEADS_DIR, 0755, true)) {
                throw new RuntimeException(sprintf('failed to mkdir: %s', F_GIT_HEADS_DIR));
            }

            if (!touch(F_GIT_HEAD)) {
                throw new RuntimeException(sprintf('failed to touch: %s', F_GIT_HEAD));
            }

            $data = sprintf('ref: %s/%s', F_GIT_HEADS_DIR, Branch::BASE);
            if (file_put_contents(F_GIT_HEAD, $data, FILE_APPEND) === false) {
                throw new RuntimeException(sprintf('failed to write in file: %s', F_GIT_HEAD));
            }

            $this->io->success(sprintf('initialized empty Git repository in %s', F_GIT_DIR));

            return Result::Success;
        } catch (FileAlreadyExistsException $ex) {
            $this->io->warning($ex->getMessage());

            return Result::Invalid;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::Failure;
        }
    }
}
