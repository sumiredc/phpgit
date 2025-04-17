<?php

declare(strict_types=1);

namespace Phpgit\Exception;

use Exception;

final class CannotAddIndexException extends Exception
{
    protected $message = 'cannot add to the index';
}
