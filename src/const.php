<?php
// TODO: .env で値を管理
declare(strict_types=1);

// git index
const GIT_INDEX_SIGNATURE = 'DIRC';
const GIT_INDEX_VERSION = 2;
const GIT_INDEX_HEADER_LENGTH = 12;
const GIT_INDEX_ENTRY_HEADER_LENGTH = 62;

// git-relative path (ex: refs/heads)
const GIT_TRACKING_ROOT = 'project';
const GIT_DIR = '.git';
const GIT_OBJECTS_DIR = 'objects';
const GIT_HEADS_DIR = 'refs/heads';
const GIT_HEAD = 'HEAD';
const GIT_INDEX = 'index';
const GIT_CONFIG = 'config';

// absolute path (ex: /{project-path}/{gitdir}/refs/heads)
define('F_GIT_TRACKING_ROOT', sprintf("%s/%s", getcwd(), GIT_TRACKING_ROOT));
const F_GIT_DIR = F_GIT_TRACKING_ROOT . '/' . GIT_DIR;
const F_GIT_OBJECTS_DIR = F_GIT_DIR . '/' . GIT_OBJECTS_DIR;
const F_GIT_HEADS_DIR = F_GIT_DIR . '/' . GIT_HEADS_DIR;
const F_GIT_HEAD = F_GIT_DIR . '/' . GIT_HEAD;
const F_GIT_INDEX = F_GIT_DIR . '/' . GIT_INDEX;
const F_GIT_CONFIG = F_GIT_DIR . '/' . GIT_CONFIG;

const GIT_BASE_BRANCH = 'main';
const GIT_REPOSITORY_FORMAT_VERSION = 0;
const GIT_FILEMODE = true;
const GIT_BARE = false;
const GIT_LOG_ALL_REF_UPDATES = true;
const GIT_IGNORE_CASE = true;
const GIT_PRE_COMPOSE_UNICODE = true;
