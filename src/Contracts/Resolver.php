<?php

namespace Smpl\Container\Contracts;

interface Resolver
{
    public function isShared(): bool;

    public function resolve(array $arguments = [], bool $fresh = false): mixed;
}