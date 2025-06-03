<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Request\Request;
use Symfony\Component\Console\Command\Command;

arch()->preset()->php()->ignoring('var_export');

arch('globals: should not use functions')->expect(['var_dump'])
    ->not->toBeUsed();

arch('globals: should use strict types')->expect(['Phpgit', 'Test'])
    ->toUseStrictTypes();

arch('Command')->expect('Phpgit\Command')
    ->classes()->toExtend(Command::class)
    ->classes()->toImplement(CommandInterface::class)
    ->classes()->toBeFinal();

arch('Domain\Repository')->expect('Phpgit\Doamin\Repository')
    ->toBeInterface();

arch('Exception')->expect('Phpgit\Exception')
    ->toExtend(Exception::class)
    ->toBeFinal()
    ->not->toBeUsedIn('Phpgit\Domain')
    ->not->toBeUsedIn('Phpgit\Infra');

arch('Infra')->expect('Phpgit\Infra')
    ->not->toBeUsedIn('Phpgit\Service')
    ->not->toBeUsedIn('Phpgit\UseCase');

arch('Request')->expect('Phpgit\Request')
    // ->toExtend(Request::class)->ignoring('Phpgit\Request\Request')
    ->toBeFinal()->ignoring('Phpgit\Request\Request');

arch('UseCase')->expect('Phpgit\UseCase')
    ->toBeInvokable()
    ->toBeFinal();

arch('Service')->expect('Phpgit\Service')
    ->toBeInvokable()
    ->classes()->toBeReadonly()
    ->classes()->toBeFinal();
