<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\HashMap;
use Phpgit\Domain\TreeEntry;
use Phpgit\Domain\TreeObject;

interface TreeToFlatEntriesServiceInterface
{
    /**
     * @return HashMap<TreeEntry>
     */
    public function __invoke(TreeObject $tree): HashMap;
}
