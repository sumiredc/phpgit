<?php

declare(strict_types=1);

namespace Phpgit\Exception;

use Exception;

final class FileAlreadyExistsException extends Exception
{
    protected $message = 'file already exists';
}
