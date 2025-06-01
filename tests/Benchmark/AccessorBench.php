<?php

declare(strict_types=1);

use PhpBench\Attributes;

#[Attributes\Warmup(2)]
#[Attributes\Revs(100000)]
#[Attributes\Iterations(10)]
final class AccessorBench
{
    public function benchClassMethod(): void
    {
        $method = new ClassMethod('path');
        $method->fullPath();
    }

    public function benchGetterProperty(): void
    {
        $property = new GetterProperty('path');
        $property->fullPath;
    }
}

readonly final class ClassMethod
{
    public function __construct(private readonly string $path) {}

    public function fullPath(): string
    {
        return "full/{$this->path}";
    }
}

final class GetterProperty
{
    public string $fullPath {
        get => "full/{$this->path}";
    }

    public function __construct(private readonly string $path) {}
}
