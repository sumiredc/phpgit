<?php

namespace Phpgit\Domain;

enum Result
{
    case Success;
    case Invalid;
    case Failure;
}
