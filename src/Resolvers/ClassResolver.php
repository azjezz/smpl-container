<?php

namespace Smpl\Container\Resolvers;

use Psl\Str;
use Psl\Iter;
use Psl\Dict;
use ReflectionClass;
use ReflectionProperty;
use Smpl\Container\Attributes\Inject;
use Smpl\Container\Exceptions\InvalidArgument;

class ClassResolver extends BaseResolver
{
    private string $className;

    private bool $shared;

    private object $instance;

    public function __construct(string $className, bool $shared = false)
    {
        $this->className = $className;
        $this->shared    = $shared;
    }

    private function collectPropertyDependencies(ReflectionClass $reflection): array
    {
        return Dict\filter(
            $reflection->getProperties(),
            fn(ReflectionProperty $property) => ! Iter\is_empty($property->getAttributes(Inject::class)) && ! $property->isStatic()
        );
    }

    private function injectPropertyDependencies(object $instance, array $properties, array &$arguments): void
    {
        foreach ($properties as $property) {
            /**
             * @var ReflectionProperty $property
             */
            $property->setAccessible(true);
            $property->setValue($instance, $this->getPropertyValue($property, $arguments));
        }
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function resolve(array $arguments = [], bool $fresh = false): ?object
    {
        if (! $fresh && isset($this->instance) && $this->isShared()) {
            return $this->instance;
        }

        $reflection           = new ReflectionClass($this->className);
        $propertyDependencies = $this->collectPropertyDependencies($reflection);

        if (Iter\is_empty($propertyDependencies)) {
            $instance = $this->newInstance($reflection, $arguments);
        } else {
            $constructorArguments = [];
            $instance             = $this->newInstance($reflection, $constructorArguments, false);
            $constructor          = $reflection->getConstructor();
            $requiredArguments    = $constructor === null ? Iter\count($propertyDependencies) : Iter\count($propertyDependencies) + $constructor->getNumberOfParameters();

            if (($count = Iter\count($arguments)) !== $requiredArguments && ! $this->getContainer()->shouldAutowire()) {
                throw new InvalidArgument(Str\format(
                    'Resolving class %s requires %s arguments, %s provided',
                    $this->className,
                    $requiredArguments,
                    $count
                ));
            }

            $this->injectPropertyDependencies($instance, $propertyDependencies, $arguments);

            if ($constructor !== null) {
                $this->callMethod($instance, $constructor, $arguments);
            }
        }

        if ($instance !== null && ! isset($this->instance) && $this->isShared()) {
            $this->instance = $instance;
        }

        return $instance;
    }
}