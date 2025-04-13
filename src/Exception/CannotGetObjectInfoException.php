<?php

declare(strict_types=1);

namespace Phpgit\Exception;

use Exception;

final class CannotGetObjectInfoException extends Exception
{
    protected $message = 'could not get object info';
    protected $code = 400;
}
