<?php

namespace Smpl\Container;

use Psl\Iter;
use Psl\Class;
use Psl\Type;
use Closure;
use ReflectionClass;
use ReflectionException;
use Smpl\Container\Attributes\ProvidedBy;
use Smpl\Container\Contracts\Resolver;
use Smpl\Container\Exceptions\InvalidResolver;
use Smpl\Container\Resolvers\ClassResolver;
use Smpl\Container\Resolvers\ClosureResolver;
use Smpl\Container\Resolvers\MethodResolver;

class Container implements Contracts\Container
{
    private static self $instance;

    public static function instance(): static
    {
        if (! isset(self::$instance)) {
            self::$instance = new static;
            self::$instance->bind(__CLASS__, fn() => self::instance());
        }

        return self::$instance;
    }

    private bool $autowire = true;

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

    public function bind(string $abstract, string|array|null|object $concrete = null, bool $shared = false): static
    {
        $concrete ??= $abstract;
        $resolver = $this->createResolver($concrete, $shared);

        if ($resolver !== null) {
            $this->resolvers[$abstract] = $resolver;
        }

        return $this;
    }

    public function call(string $class, string $method, array $arguments = [], bool $fresh = false, bool $shared = false): mixed
    {
        // TODO: Handle shared method calls
        $resolver = $this->createMethodResolver($class, $method, $shared);

        if ($resolver !== null) {
            $resolver->setContainer($this);

            return $resolver->resolve($arguments);
        }

        return null;
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
        if (!Class\has_method($class, $method)) {
            throw new InvalidResolver(sprintf('Cannot create a resolver for %s::%s as the method does not exist', $class, $method));
        }

        return new MethodResolver($class, $method, $shared);
    }

    private function createResolver(object|string|array $concrete, bool $shared): ?Resolver
    {
        if ($concrete instanceof Resolver) {
            return $concrete;
        }

        if ($concrete instanceof Closure) {
            return $this->createClosureResolver($concrete, $shared);
        }

        if (is_object($concrete)) {
            // TODO: Add object resolver
        }

        if (Type\non_empty_string()->matches($concrete) && Class\exists($concrete)) {
            return $this->processClassProvider($concrete) ?? $this->createClassResolver($concrete, $shared);
        }
        
        $spec = Type\shape([Type\non_empty_string(), Type\non_empty_string()]);
        if ($spec->matches($concrete)) {
            [$class, $method] = $spec->coerce($concrete);

            return $this->createMethodResolver($class, $method, $shared);
        }

        return null;
    }

    public function disableAutowiring(): static
    {
        $this->autowire = false;

        return $this;
    }

    public function enableAutowiring(): static
    {
        $this->autowire = true;

        return $this;
    }

    public function hasBinding(string $abstract): bool
    {
        return Iter\contains_key($this->resolvers, $abstract);
    }

    public function hasProvider(string $providerClass): bool
    {
        return Iter\contains_key($this->providers, $providerClass);
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
            $attribute  = Iter\first($reflection->getAttributes(ProvidedBy::class));

            if ($attribute !== null) {
                $providedBy = $attribute->newInstance();
                return $this->provider($providedBy->getClass())->resolver($concrete);
            }
        } catch (ReflectionException) {
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

    public function shouldAutowire(): bool
    {
        return $this->autowire;
    }
}