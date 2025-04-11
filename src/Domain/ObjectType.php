<?php

namespace Phpgit\Domain;

enum ObjectType: string
{
    case Blob = 'blob';
    case Commit = 'commit';
    case Tree = 'tree';
    case Tag = 'tag';
}
