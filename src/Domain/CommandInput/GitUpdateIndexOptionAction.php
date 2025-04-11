<?php

namespace Phpgit\Domain\CommandInput;

enum GitUpdateIndexOptionAction
{
    case Add;
    case Remove;
    case ForceRemove;
    case Replace;
}
