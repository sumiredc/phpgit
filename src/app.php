<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/const.php';

use Phpgit\Command\GitCatFileCommand;
use Phpgit\Command\GitCommitTreeCommand;
use Phpgit\Command\GitHashObjectCommand;
use Phpgit\Command\GitInitCommand;
use Phpgit\Command\GitLsFilesCommand;
use Phpgit\Command\GitRevParseCommand;
use Phpgit\Command\GitUpdateIndexCommand;
use Phpgit\Command\GitUpdateRefCommand;
use Phpgit\Command\GitWriteTreeCommand;
use Symfony\Component\Console\Application;

$app = new Application('phpgit', '0.1.0');

$app->add(new GitInitCommand());
$app->add(new GitHashObjectCommand());
$app->add(new GitCatFileCommand());
$app->add(new GitUpdateIndexCommand());
$app->add(new GitLsFilesCommand());
$app->add(new GitWriteTreeCommand());
$app->add(new GitCommitTreeCommand());
$app->add(new GitRevParseCommand());
$app->add(new GitUpdateRefCommand());

$app->run();
