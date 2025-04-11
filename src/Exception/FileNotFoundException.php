<?php

namespace Phpgit\Exception;

use Exception;

final class FileNotFoundException extends Exception
{
    protected $message = 'file not found.';
    protected $code = 404;
}
