<?php

declare(strict_types=1);

namespace Phpgit\Infra\Printer;

use Phpgit\Domain\Printer\PrinterInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class CliPrinter extends SymfonyStyle implements PrinterInterface
{

    private const SYMBOL_INSERTION = '+';
    private const SYMBOL_DELETION = '-';

    public function stackTrace(Throwable $th): void
    {
        $this->error(strval($th));
    }

    /** output plain text (include null-terminated string) */
    public function echo(string $message): void
    {
        echo $message;
    }

    public function writelnDiffStat(
        int $pathLength,
        int $diffDigits,
        string $path,
        int $insertions,
        int $deletions
    ): void {
        $isDecorated = $this->isDecorated();
        if (!$isDecorated) {
            $this->setDecorated(true);
        }
        $symbolI = str_repeat(self::SYMBOL_INSERTION, $insertions);
        $symbolD = str_repeat(self::SYMBOL_DELETION, $deletions);

        $this->writeln(sprintf(
            " %-{$pathLength}s | %{$diffDigits}d <fg=green>%s</><fg=red>%s</>",
            $path,
            $insertions + $deletions,
            $symbolI,
            $symbolD
        ));

        if (!$isDecorated) {
            $this->setDecorated(false);
        }
    }
}
