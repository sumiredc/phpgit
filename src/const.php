<?php

declare(strict_types=1);

// git index
define('GIT_INDEX_SIGNATURE', 'DIRC');
define('GIT_INDEX_VERSION', 2);

// git-relative path (ex: refs/heads)
define('GIT_TRACKING_ROOT', 'project');
define('GIT_DIR', '.phpgit');
define('GIT_OBJECTS_DIR', 'objects');
define('GIT_HEADS_DIR', 'refs/heads');
define('GIT_HEAD', 'HEAD');
define('GIT_INDEX', 'index');

// absolute path (ex: /{project-path}/{gitdir}/refs/heads)
define('F_GIT_TRACKING_ROOT', sprintf("%s/%s", getcwd(), GIT_TRACKING_ROOT));
define('F_GIT_DIR', sprintf('%s/%s', F_GIT_TRACKING_ROOT, GIT_DIR));
define('F_GIT_OBJECTS_DIR', sprintf('%s/%s', F_GIT_DIR, GIT_OBJECTS_DIR));
define('F_GIT_HEADS_DIR', sprintf('%s/%s', F_GIT_DIR, GIT_HEADS_DIR));
define('F_GIT_HEAD', sprintf('%s/%s', F_GIT_DIR, GIT_HEAD));
define('F_GIT_INDEX', sprintf('%s/%s', F_GIT_DIR, GIT_INDEX));

define('GIT_BASE_BRANCH', 'main');
