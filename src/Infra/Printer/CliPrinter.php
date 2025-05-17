<?php

declare(strict_types=1);

namespace Phpgit\Infra\Printer;

use Phpgit\Domain\Printer\PrinterInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class CliPrinter extends SymfonyStyle implements PrinterInterface
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
