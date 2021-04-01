<?php

namespace Smpl\Container\Resolvers;

use ReflectionMethod;

class MethodResolver extends BaseResolver
{
    private string $class;

    private string $method;

    private bool $shared;

    private object $instance;

    public function __construct(string $class, string $method, bool $shared)
    {
        $this->class  = $class;
        $this->method = $method;
        $this->shared = $shared;
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function resolve(array $arguments = [], bool $fresh = false): mixed
    {
        if (! $fresh && isset($this->instance) && $this->isShared()) {
            return $this->instance;
        }

        $reflection = new ReflectionMethod($this->class, $this->method);
        $instance   = $this->callMethod(
            $reflection->isStatic()
                ? null
                : $this->getContainer()->make($this->class, $arguments),
            $reflection,
            $arguments);

        if ($instance !== null && ! isset($this->instance) && $this->isShared()) {
            $this->instance = $instance;
        }

        return $instance;
    }
}