<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/const.php';

use Phpgit\Command\CatFileCommand;
use Phpgit\Command\CommitTreeCommand;
use Phpgit\Command\HashObjectCommand;
use Phpgit\Command\InitCommand;
use Phpgit\Command\LsFilesCommand;
use Phpgit\Command\GitRevParseCommand;
use Phpgit\Command\GitUpdateIndexCommand;
use Phpgit\Command\GitUpdateRefCommand;
use Phpgit\Command\GitWriteTreeCommand;
use Symfony\Component\Console\Application;

$app = new Application('phpgit', '0.1.0');

$app->add(new InitCommand());
$app->add(new HashObjectCommand());
$app->add(new CatFileCommand());
$app->add(new GitUpdateIndexCommand());
$app->add(new LsFilesCommand());
$app->add(new GitWriteTreeCommand());
$app->add(new CommitTreeCommand());
$app->add(new GitRevParseCommand());
$app->add(new GitUpdateRefCommand());

$app->run();
