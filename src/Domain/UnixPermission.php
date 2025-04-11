<?php

namespace Phpgit\Domain;

enum UnixPermission: int
{
    case Zero = 0;
    case RwxRxRx = 0755;
    case RwRR = 0644;

    public static function fromStatMode(int $mode): self
    {
        if (decoct($mode & 0777) & 0x0100) {
            return self::RwxRxRx;
        }

        return self::RwRR;
    }
}
