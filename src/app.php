<?php

require __DIR__ . '/../vendor/autoload.php';

use Phpgit\Command\GitCatFileCommand;
use Phpgit\Command\GitHashObjectCommand;
use Phpgit\Command\GitInitCommand;
use Symfony\Component\Console\Application;

$app = new Application('phpgit', '0.1.0');

$app->add(new GitInitCommand());
$app->add(new GitHashObjectCommand());
$app->add(new GitCatFileCommand());

$app->run();
