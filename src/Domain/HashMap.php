<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use Countable;
use InvalidArgumentException;
use Iterator;

/**
 * @template T
 */
final class HashMap implements Iterator, Countable
{
    private int $position = 0;

    /**
     * @param array<int,string> $keys
     * @param array<string,T> $values
     */
    private function __construct(
        private array $keys,
        private array $values
    ) {}

    public static function new(): self
    {
        return new self([], []);
    }

    /**
     * @param array<string,T> $values
     */
    public static function parse(array $values): self
    {
        /**
         * @var array<int,string> $keys
         */
        $keys = [];

        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException(sprintf(
                    'key is not string: %s is type %s',
                    strval($key),
                    gettype($key)
                ));
            }

            if (is_null($value)) {
                throw new InvalidArgumentException(sprintf(
                    'null value is not allowed: %s',
                    $key
                ));
            }

            $keys[] = $key;
        }

        return new self($keys, $values);
    }

    /** 
     * @param T $values 
     */
    public function set(string $key, $value): void
    {
        if (is_null($value)) {
            throw new InvalidArgumentException('null value is not allowed');
        }

        $this->keys[] = $key;
        $this->values[$key] = $value;
    }

    /** 
     * @return T|null 
     */
    public function get(string $key)
    {
        return $this->values[$key] ?? null;
    }

    public function delete(string $key): void
    {
        unset($this->values[$key]);
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * @see https://www.php.net/manual/en/class.iterator.php
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @see https://www.php.net/manual/en/class.iterator.php
     * 
     * @return T
     */
    public function current(): mixed
    {
        return $this->values[$this->keys[$this->position]] ?? null;
    }

    /**
     * @see https://www.php.net/manual/en/class.iterator.php
     */
    public function key(): ?string
    {
        return $this->keys[$this->position] ?? null;
    }

    /**
     * @see https://www.php.net/manual/en/class.iterator.php
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @see https://www.php.net/manual/en/class.iterator.php
     */
    public function valid(): bool
    {
        return isset($this->keys[$this->position]);
    }

    /**
     * @see https://www.php.net/manual/en/class.countable.php
     */
    public function count(): int
    {
        return count($this->keys);
    }
}
