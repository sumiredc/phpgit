<?php

namespace Phpgit\Domain\CommandArgument;

enum GitCatFileTypeArgument
{
    case Type;
    case Size;
    case Exists;
    case PrettyPrint;
}
