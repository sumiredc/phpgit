<?php

namespace Phpgit\Domain;

use Symfony\Component\Console\Exception\InvalidOptionException;
use ValueError;

enum UnixPermission: int
{
    case Zero = 0;
    case RwxRxRx = 0755;
    case RwRR = 0644;

    /** NOTE: 10 -> 8 */
    public static function fromDecoct(int $dec): self
    {
        return match ($dec) {
            self::Zero->value => self::Zero,
            intval(self::RwxRxRx->value) => self::RwxRxRx,
            intval(self::RwRR->value) => self::RwRR,
            default => throw new ValueError(sprintf('value error: %d', $dec)),
        };
    }

    public static function fromStatMode(int $mode): self
    {
        if (decoct($mode & 0777) & 0x0100) {
            return self::RwxRxRx;
        }

        return self::RwRR;
    }
}
