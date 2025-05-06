<?php

declare(strict_types=1);

namespace Phpgit\Exception;

use Exception;

final class InvalidObjectNameException extends Exception
{
    protected $message = 'invalid object name';
}
