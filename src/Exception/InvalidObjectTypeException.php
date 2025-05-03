<?php

declare(strict_types=1);

namespace Phpgit\Exception;

use Exception;

final class InvalidObjectTypeException extends Exception
{
    protected $message = 'invalid object type';
}
