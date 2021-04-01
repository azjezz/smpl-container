<?php

namespace Smpl\Container\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Resolves
{
    private array $classes;

    public function __construct(string ...$classes)
    {
        $this->classes = $classes;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }
}