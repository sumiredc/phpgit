<?php

declare(strict_types=1);

use Phpgit\Env;

$appEnv = getenv('APP_ENV', true);
$env = Env::load($appEnv ? ".env.$appEnv" : '.env');

// git index
const GIT_INDEX_SIGNATURE = 'DIRC';
const GIT_INDEX_VERSION = 2;
const GIT_INDEX_HEADER_LENGTH = 12;
const GIT_INDEX_ENTRY_HEADER_LENGTH = 62;

// git-relative path (ex: refs/heads)
define('GIT_TRACKING_ROOT', $env['TRACKING_ROOT']);
const GIT_DIR = '.git';
const GIT_OBJECTS_DIR = 'objects';
const GIT_REFS_HEADS_DIR = 'refs/heads';
const GIT_REFS_REMOTES_DIR = 'refs/remotes';
const GIT_REFS_TAGS_DIR = 'refs/tags';
const GIT_REFS_NOTES_DIR = 'refs/notes';
const GIT_REFS_STASH_DIR = 'refs/stash';
const GIT_REFS_REPLACE_DIR = 'refs/replace';
const GIT_REFS_BISECT_DIR = 'refs/bisect';
const GIT_HEAD = 'HEAD';
const GIT_INDEX = 'index';
const GIT_CONFIG = 'config';

// absolute path (ex: /{project-path}/{gitdir}/refs/heads)
define('F_GIT_TRACKING_ROOT', sprintf("%s/%s", $env['CURRENT_DIR'] ?: getcwd(), GIT_TRACKING_ROOT));
const F_GIT_DIR = F_GIT_TRACKING_ROOT . '/' . GIT_DIR;
const F_GIT_OBJECTS_DIR = F_GIT_DIR . '/' . GIT_OBJECTS_DIR;
const F_GIT_REFS_HEADS_DIR = F_GIT_DIR . '/' . GIT_REFS_HEADS_DIR;
const F_GIT_REFS_REMOTES_DIR = F_GIT_DIR . '/' . GIT_REFS_REMOTES_DIR;
const F_GIT_REFS_TAGS_DIR = F_GIT_DIR . '/' . GIT_REFS_TAGS_DIR;
const F_GIT_REFS_NOTES_DIR = F_GIT_DIR . '/' . GIT_REFS_NOTES_DIR;
const F_GIT_REFS_STASH_DIR = F_GIT_DIR . '/' . GIT_REFS_STASH_DIR;
const F_GIT_REFS_REPLACE_DIR = F_GIT_DIR . '/' . GIT_REFS_REPLACE_DIR;
const F_GIT_REFS_BISECT_DIR = F_GIT_DIR . '/' . GIT_REFS_BISECT_DIR;
const F_GIT_HEAD = F_GIT_DIR . '/' . GIT_HEAD;
const F_GIT_INDEX = F_GIT_DIR . '/' . GIT_INDEX;
const F_GIT_CONFIG = F_GIT_DIR . '/' . GIT_CONFIG;

define('GIT_BASE_BRANCH', $env['BASE_BRANCH']);
define('GIT_DEFAULT_USER_NAME', $env['DEFAULT_USER_NAME']);
define('GIT_DEFAULT_USER_EMAIL', $env['DEFAULT_USER_EMAIL']);
define('GIT_REPOSITORY_FORMAT_VERSION', $env['REPOSITORY_FORMAT_VERSION']);
define('GIT_FILEMODE', $env['FILEMODE']);
define('GIT_BARE', $env['BARE']);
define('GIT_LOG_ALL_REF_UPDATES', $env['LOG_ALL_REF_UPDATES']);
define('GIT_IGNORE_CASE', $env['IGNORE_CASE']);
define('GIT_PRE_COMPOSE_UNICODE', $env['PRE_COMPOSE_UNICODE']);

unset($env, $appEnv);
