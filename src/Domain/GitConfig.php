<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;

final class GitConfig
{
    public private(set) int $repositoryFormatVersion {
        get => $this->repositoryFormatVersion;
        /** @throws InvalidArgumentException */
        set(int $v) {
            if (!in_array($v, [0, 1], true)) {
                throw new InvalidArgumentException(sprintf('invalid value: %d', $v));
            }
            $this->repositoryFormatVersion = $v;
        }
    }

    private function __construct(
        int $repositoryFormatVersion,
        public private(set) bool $filemode,
        public private(set) bool $bare,
        public private(set) bool $logAllRefUpdates,
        public private(set) bool $ignoreCase,
        public private(set) bool $preComposeUnicode,
        public private(set) string $userName,
        public private(set) string $userEmail,
    ) {
        $this->repositoryFormatVersion = $repositoryFormatVersion;
    }

    public static function new(
        int $repositoryFormatVersion,
        bool $filemode,
        bool $bare,
        bool $logAllRefUpdates,
        bool $ignoreCase,
        bool $preComposeUnicode,
        string $userName,
        string $userEmail,
    ): self {
        return new self(
            $repositoryFormatVersion,
            $filemode,
            $bare,
            $logAllRefUpdates,
            $ignoreCase,
            $preComposeUnicode,
            $userName,
            $userEmail
        );
    }
}
