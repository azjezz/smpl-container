<?php

namespace Smpl\Container\Tests\Fixtures;

use Smpl\Container\Attributes\ProvidedBy;

#[ProvidedBy(class: DependencyProvider::class)]
class AnotherDependency
{
    private bool $foo;

    private string $that;

    public function __construct(bool $foo, string $that)
    {
        $this->foo  = $foo;
        $this->that = $that;
    }

    public function getThat(): string
    {
        return $this->that;
    }

    public function isFoo(): bool
    {
        return $this->foo;
    }
}