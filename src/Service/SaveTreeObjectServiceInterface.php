<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\SegmentTree;
use UnexpectedValueException;

interface SaveTreeObjectServiceInterface
{
    /**
     * @throws UnexpectedValueException
     */
    public function __invoke(SegmentTree $segmentTree): ObjectHash;
}
