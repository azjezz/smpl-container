<?php

namespace Smpl\Container\Resolvers;

use Closure;

class ClosureResolver extends BaseResolver
{
    private Closure $closure;

    private bool $shared;

    private object $instance;

    public function __construct(Closure $closure, bool $shared)
    {
        $this->closure = $closure;
        $this->shared  = $shared;
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function resolve(array $arguments = [], bool $fresh = false): object
    {
        if (! $fresh && isset($this->instance) && $this->isShared()) {
            return $this->instance;
        }

        $instance = call_user_func_array($this->closure, $arguments);

        if ($instance !== null && ! isset($this->instance) && $this->isShared()) {
            $this->instance = $instance;
        }

        return $instance;
    }
}