<?php

namespace Smpl\Container\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ProvidedBy
{
    private string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }
}