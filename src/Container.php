<?php

namespace Smpl\Container;

use Closure;
use ReflectionClass;
use Smpl\Container\Attributes\ProvidedBy;
use Smpl\Container\Contracts\Resolver;
use Smpl\Container\Resolvers\ClassResolver;
use Smpl\Container\Resolvers\ClosureResolver;
use Smpl\Container\Resolvers\MethodResolver;

class Container
{
    /**
     * @var array<class-string, \Smpl\Container\Provider>
     */
    private array $providers = [];

    /**
     * @var array<class-string, \Smpl\Container\Contracts\Resolver>
     */
    private array $resolvers = [];

    /**
     * @var array<class-string, class-string>
     */
    private array $aliases = [];

    public function alias(string $abstract, string ...$aliases): static
    {
        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $abstract;
        }

        return $this;
    }

    public function bind(string $abstract, Closure|string|array|null $concrete = null, bool $shared = false): static
    {
        $concrete ??= $abstract;
        $resolver = $this->createResolver($concrete, $shared);

        if ($resolver !== null) {
            $this->resolvers[$abstract] = $resolver;
        }

        return $this;
    }

    private function createClassResolver(string $class, bool $shared): ClassResolver
    {
        return new ClassResolver($class, $shared);
    }

    private function createClosureResolver(Closure $concrete, bool $shared): ClosureResolver
    {
        return new ClosureResolver($concrete, $shared);
    }

    private function createMethodResolver(string $class, string $method, bool $shared): MethodResolver
    {
        if (! method_exists($class, $method)) {
            // TODO: Throw an exception
        }

        return new MethodResolver($class, $method, $shared);
    }

    private function createResolver(Closure|string|array $concrete, bool $shared): ?Resolver
    {
        if (is_string($concrete)) {
            if (function_exists($concrete)) {
                // TODO: Return function exists
            }

            if (class_exists($concrete)) {
                return $this->processClassProvider($concrete) ?? $this->createClassResolver($concrete, $shared);
            }
        }

        if ($concrete instanceof Closure) {
            return $this->createClosureResolver($concrete, $shared);
        }

        if (is_array($concrete)) {
            if (count($concrete) !== 2) {
                // TODO: Throw an exception
            }

            [$class, $method] = $concrete;

            return $this->createMethodResolver($class, $method, $shared);
        }

        return null;
    }

    public function hasBinding(string $abstract): bool
    {
        return isset($this->resolvers[$abstract]);
    }

    public function hasProvider(string $providerClass): bool
    {
        return isset($this->providers[$providerClass]);
    }

    public function make(string $class, array $arguments = [], bool $fresh = false, bool $shared = false)
    {
        $resolver = $this->resolver($class);

        if ($resolver === null) {
            $resolver                = $this->createResolver($class, $shared);
            $this->resolvers[$class] = $resolver;
        }

        if ($resolver !== null) {
            $resolver->setContainer($this);
            return $resolver->resolve($arguments, $fresh);
        }

        return null;
    }

    private function processClassProvider(string $concrete): ?Resolver
    {
        try {
            $reflection = new ReflectionClass($concrete);
            $attribute  = $reflection->getAttributes(ProvidedBy::class)[0] ?? null;

            if ($attribute !== null) {
                $providedBy = $attribute->newInstance();
                return $this->provider($providedBy->getClass())->resolver($concrete);
            }
        } catch (\ReflectionException $e) {
        }

        return null;
    }

    public function provider(string $providerClass): static
    {
        $provider = new Provider($providerClass);
        $provider->process($this);

        $this->providers[$providerClass] = $provider;

        return $this;
    }

    public function resolver(string $abstract): ?Resolver
    {
        return $this->resolvers[$abstract] ?? null;
    }
}