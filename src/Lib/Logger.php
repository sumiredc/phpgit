<?php

declare(strict_types=1);

namespace Phpgit\Lib;

use DateTimeImmutable;
use DateTimeZone;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;

final class Logger
{
    public static function console(): LoggerInterface
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Tokyo'));
        $path = sprintf('%s/logs/console-%s.log', getcwd(), $today->format('Y-m-d'));
        $logger = new MonologLogger('console');
        $logger->pushHandler(new StreamHandler($path), Level::Info);

        return $logger;
    }
}
