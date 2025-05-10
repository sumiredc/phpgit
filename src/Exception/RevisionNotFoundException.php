<?php

declare(strict_types=1);

namespace Phpgit\Exception;

use Exception;

final class RevisionNotFoundException extends Exception
{
    protected $message = 'revision not found';
    protected $code = 404;
}
