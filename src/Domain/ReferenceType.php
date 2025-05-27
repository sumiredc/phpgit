<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use UnhandledMatchError;

enum ReferenceType
{
    case Local;
    case Remote;
    case Tag;
    case Note;
    case Stash;
    case Replace;
    case Bisect;

    public function prefix(): string
    {
        return match ($this) {
            self::Local => GIT_REFS_HEADS_DIR,
            self::Remote => GIT_REFS_REMOTES_DIR,
            self::Tag => GIT_REFS_TAGS_DIR,
            self::Note => GIT_REFS_NOTES_DIR,
            self::Stash => GIT_REFS_STASH_DIR,
            self::Replace => GIT_REFS_REPLACE_DIR,
            self::Bisect => GIT_REFS_BISECT_DIR,
            default => throw new UnhandledMatchError(
                sprintf('Unhandled enum case: %s', $this->name)
            ), // @codeCoverageIgnore
        };
    }
}
