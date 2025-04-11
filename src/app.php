<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/const.php';

use Phpgit\Command\GitCatFileCommand;
use Phpgit\Command\GitHashObjectCommand;
use Phpgit\Command\GitInitCommand;
use Phpgit\Command\GitUpdateIndexCommand;
use Symfony\Component\Console\Application;

$app = new Application('phpgit', '0.1.0');

$app->add(new GitInitCommand());
$app->add(new GitHashObjectCommand());
$app->add(new GitCatFileCommand());
$app->add(new GitUpdateIndexCommand());

$app->run();
