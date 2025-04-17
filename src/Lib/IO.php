<?php

declare(strict_types=1);

namespace Phpgit\Lib;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class IO extends SymfonyStyle implements IOInterface, StyleInterface, OutputInterface
{
    public function stackTrace(Throwable $th): void
    {
        $this->error(strval($th));
    }

    /** output plain text (include null-terminated string) */
    public function echo(string $message): void
    {
        echo $message;
    }
}
