<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use ValueError;

enum UnixPermission: int
{
    case Zero = 0;
    case RwxRxRx = 0755;
    case RwRR = 0644;

    /**
     * NOTE: the lower 9bit in object flags
     */
    public static function parseFlags(int $flags): self
    {
        $dec = $flags & 0b1_1111_1111;

        return match ($dec) {
            self::Zero->value => self::Zero,
            intval(self::RwxRxRx->value) => self::RwxRxRx,
            intval(self::RwRR->value) => self::RwRR,
            default => throw new ValueError(sprintf('value error: %d', $flags)),
        };
    }

    /**
     * Owner has execution authority -> 0755
     * Onwer don't has execution authority -> 0644
     */
    public static function fromStatMode(int $mode): self
    {
        $permission = decoct($mode & 0777);
        $owner = intval($permission[0]);
        if ($owner & 1 === 1) {
            return self::RwxRxRx;
        }

        return self::RwRR;
    }
}
