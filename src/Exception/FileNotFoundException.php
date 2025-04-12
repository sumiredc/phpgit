<?php

declare(strict_types=1);

namespace Phpgit\Exception;

use Exception;

final class FileNotFoundException extends Exception
{
    protected $message = 'file not found';
    protected $code = 404;
}
