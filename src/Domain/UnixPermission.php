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
     * NOTE: 10 -> UnixPermission
     * 
     * case1: 0 -> 0
     * case2: 493 -> 0755
     * case3: 420 -> 0644
     * 
     * other: -> ValueError
     */
    public static function fromDec(int $dec): self
    {
        return match ($dec) {
            self::Zero->value => self::Zero,
            intval(self::RwxRxRx->value) => self::RwxRxRx,
            intval(self::RwRR->value) => self::RwRR,
            default => throw new ValueError(sprintf('value error: %d', $dec)),
        };
    }

    // /** 
    //  * @deprecated 
    //  * 
    //  * NOTE: (string)8 -> UnixPermission
    //  * 
    //  * ex1: 100755 -> 0755
    //  * ex2: 100644 -> 0644
    //  */
    // public static function fromOct(string $oct): self
    // {
    //     $mode = intval($oct, 8);

    //     return self::fromStatMode($mode);
    // }

    public static function fromStatMode(int $mode): self
    {
        $permission = decoct($mode & 0777);
        $owner = intval($permission[0]);
        if ($owner & 1 === 1) {
            return self::RwxRxRx;
        }

        return self::RwRR;
    }

    // /** 
    //  * @deprecated 
    //  * 
    //  * ex1: 0755 -> 100755
    //  * ex2: 0644 -> 100644
    //  */
    // public function mode(): int
    // {
    //     if ($this === self::Zero) {
    //         return 0;
    //     }

    //     return octdec(sprintf("100%o", $this->value));
    // }
}
