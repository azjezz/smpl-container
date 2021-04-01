<?php

namespace Smpl\Container\Tests\Fixtures;

use Smpl\Container\Attributes\ProvidedBy;

#[ProvidedBy(class: DependencyProvider::class)]
class ADependency
{
    private bool $foo;

    public function __construct(bool $foo)
    {
        $this->foo = $foo;
    }

    public function isFoo(): bool
    {
        return $this->foo;
    }
}