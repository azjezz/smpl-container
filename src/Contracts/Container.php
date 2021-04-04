<?php

namespace Smpl\Container\Contracts;

use Closure;

interface Container
{
    public function alias(string $abstract, string ...$aliases): static;

    public function bind(string $abstract, string|array|null|object $concrete = null, bool $shared = false): static;

    public function call(string $class, string $method, array $arguments = [], bool $fresh = false, bool $shared = false): mixed;

    public function disableAutowiring(): static;

    public function enableAutowiring(): static;

    public function hasBinding(string $abstract): bool;

    public function hasProvider(string $providerClass): bool;

    public function make(string $class, array $arguments = [], bool $fresh = false, bool $shared = false);

    public function provider(string $providerClass): static;

    public function resolver(string $abstract): ?Resolver;

    public function shouldAutowire(): bool;
}