<?php

declare(strict_types=1);

namespace Phpgit\Domain;

final class DiffState
{
    private const BOTH_EXISTS_FILE = 0;
    private const ADDED_FILE = 1;
    private const DROPED_FILE = 2;

    public int $total {
        get => $this->insertions + $this->deletions;
    }

    private function __construct(
        public readonly string $path,
        public private(set) int $insertions,
        public private(set) int $deletions,
        private int $fileState,
    ) {}

    public static function new(string $path): self
    {
        return new DiffState($path, 0, 0, self::BOTH_EXISTS_FILE);
    }

    public function insert(): void
    {
        $this->insertions++;
    }

    public function delete(): void
    {
        $this->deletions++;
    }

    public function addedFile(): void
    {
        $this->fileState = self::ADDED_FILE;
    }

    public function isAddedFile(): bool
    {
        return $this->fileState === self::ADDED_FILE;
    }

    public function dropedFile(): void
    {
        $this->fileState = self::DROPED_FILE;
    }

    public function isDropedFile(): bool
    {
        return $this->fileState === self::DROPED_FILE;
    }

    public function isChanged(): bool
    {
        return $this->isAddedFile()
            || $this->isDropedFile()
            || $this->insertions > 0
            || $this->deletions > 0;
    }
}
