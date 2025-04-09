<?php

require __DIR__ . '/../vendor/autoload.php';

use Phpgit\Command\GitHashObjectCommand;
use Phpgit\Command\GitInitCommand;
use Symfony\Component\Console\Application;

$application = new Application('phpgit', '0.1.0');

$application->add(new GitInitCommand());
$application->add(new GitHashObjectCommand());

$application->run();
