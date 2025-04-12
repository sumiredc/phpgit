<?php

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
}
