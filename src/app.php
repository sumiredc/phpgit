<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/const.php';

use Phpgit\Command\CatFileCommand;
use Phpgit\Command\CommitTreeCommand;
use Phpgit\Command\HashObjectCommand;
use Phpgit\Command\InitCommand;
use Phpgit\Command\LsFilesCommand;
use Phpgit\Command\RevParseCommand;
use Phpgit\Command\UpdateIndexCommand;
use Phpgit\Command\UpdateRefCommand;
use Phpgit\Command\WriteTreeCommand;
use Symfony\Component\Console\Application;

$app = new Application('phpgit', '0.1.0');

$app->add(new InitCommand());
$app->add(new HashObjectCommand());
$app->add(new CatFileCommand());
$app->add(new UpdateIndexCommand());
$app->add(new LsFilesCommand());
$app->add(new WriteTreeCommand());
$app->add(new CommitTreeCommand());
$app->add(new RevParseCommand());
$app->add(new UpdateRefCommand());

$app->run();
