<?php

namespace Smpl\Container\Resolvers;

use Psl\Class;
use Psl\Iter;
use Psl\Str;
use Psl\Type;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Smpl\Container\Container;
use Smpl\Container\Contracts\Resolver;
use Smpl\Container\Exceptions\InvalidArgument;

abstract class BaseResolver implements Resolver
{
    private Container $container;

    protected function callMethod(?object $instance, ReflectionMethod $method, array &$arguments = [])
    {
        return $method->invokeArgs($instance, $this->resolveMethodArguments($method, $arguments));
    }

    protected function checkType(?ReflectionType $type, mixed $value, bool $allowNull = true): bool
    {
        if ($type === null) {
            return true;
        }

        if ($type instanceof ReflectionNamedType) {
            if ($allowNull && $value === null && $type->allowsNull()) {
                return true;
            }

            return $type->getName() === get_debug_type($value);
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($this->checkType($unionType, $value, false)) {
                    return true;
                }
            }

            if ($allowNull && $value === null && $type->allowsNull()) {
                return true;
            }
        }

        return false;
    }

    private function clearArgument(ReflectionParameter|ReflectionProperty $reflectedComponent, array &$arguments): void
    {
        // If there was a parameter found based on name we're going to want to remove that from
        // the arguments array so that it isn't used for something else.
        if (isset($arguments[$reflectedComponent->getName()])) {
            unset($arguments[$reflectedComponent->getName()]);
        }

        // If it was found based on position we're going to want to remove that too.
        if (Class\has_method($reflectedComponent::class, 'getPosition') && isset($arguments[$reflectedComponent->getPosition()])) {
            unset($arguments[$reflectedComponent->getPosition()]);
        }
    }

    protected function getContainer(): Container
    {
        if (! isset($this->container)) {
            // If there's no container we'll set the instance using the singleton methods
            $this->setContainer(Container::instance());
        }

        return $this->container;
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    protected function getPropertyValue(ReflectionProperty $property, array &$arguments = [])
    {
        $resolved = null;

        if (!Iter\is_empty($arguments)) {
            $argument = $arguments[$property->getName()] ?? null;

            if ($argument !== null) {
                $propertyType = $property->getType();

                if (is_array($argument) && $propertyType !== null && class_exists($propertyType->getName()) && $this->getContainer()->shouldAutowire()) {
                    // If the parameter type is actually a class but the argument we have is an array, it means
                    // that we don't have the argument, but the arguments for resolving the parameter.
                    $resolved = $this->resolvedTypedValue($propertyType, $arguments);
                    $this->clearArgument($property, $arguments);
                } else if ($this->checkType($propertyType, $argument)) {
                    $this->clearArgument($property, $arguments);

                    return $argument;
                } else {
                    throw new InvalidArgument(sprintf('Invalid type provided for argument as property %s', $property->getName()));
                }
            }
        }

        if ($resolved === null && $this->getContainer()->shouldAutowire()) {
            $resolved = $this->resolvedTypedValue($property->getType());
        }

        if ($resolved === null) {
            $resolved = $property->hasDefaultValue() ? $property->getDefaultValue() : null;
        }

        return $resolved;
    }

    protected function newInstance(ReflectionClass $reflectionClass, array &$arguments = [], bool $useConstructor = true): object
    {
        if (! $useConstructor) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return $reflectionClass->newInstance();
        }

        if (! $this->getContainer()->shouldAutowire() && ($count = Iter\count($arguments)) !== $constructor->getNumberOfParameters()) {
            throw new InvalidArgument(Str\format(
                'The %s constructor has %s parameters, %s arguments provided',
                $reflectionClass->getName(),
                $constructor->getNumberOfParameters(),
                $count
            ));
        }

        $arguments = $this->resolveMethodArguments($constructor, $arguments);

        return $reflectionClass->newInstanceArgs($arguments);
    }

    protected function resolveMethodArguments(ReflectionMethod $method, array &$arguments = []): array
    {
        $parameters = $method->getParameters();

        if (Iter\is_empty($parameters)) {
            return [];
        }

        if (! $this->getContainer()->shouldAutowire() && ($count = Iter\count($arguments)) !== $method->getNumberOfParameters()) {
            throw new InvalidArgument(Str\format(
                'The method %s has %s parameters, %s arguments provided',
                $method->getName(),
                $method->getNumberOfParameters(),
                $count
            ));
        }

        $methodArguments = [];

        foreach ($parameters as $parameter) {
            $argument = $arguments[$parameter->getName()] ?? $arguments[$parameter->getPosition()] ?? null;
            $resolved = null;

            if ($argument !== null) {
                $parameterType = $parameter->getType();

                if ($parameterType !== null && Class\exists($parameterType->getName()) && $this->getContainer()->shouldAutowire() && Type\dict(Type\array_key(), Type\mixed())->matches($argument)) {
                    // If the parameter type is actually a class but the argument we have is an array, it means
                    // that we don't have the argument, but the arguments for resolving the parameter.
                    $resolved = $this->resolvedTypedValue($parameterType, $arguments);
                    $this->clearArgument($parameter, $arguments);
                } else if ($this->checkType($parameterType, $argument)) {
                    $methodArguments[$parameter->getName()] = $argument;
                    $this->clearArgument($parameter, $arguments);

                    continue;
                } else {
                    throw new InvalidArgument(Str\format('Invalid type provided for parameter %s', $parameter->getName()));
                }
            }

            if ($resolved === null && $this->getContainer()->shouldAutowire()) {
                $resolved = $this->resolvedTypedValue($parameter->getType());
            }

            if ($resolved === null) {
                $resolved = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            }

            $methodArguments[$parameter->getName()] = $resolved;
        }

        return $methodArguments;
    }

    protected function resolvedTypedValue(?ReflectionType $type, array $arguments = [])
    {
        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionNamedType) {
            if (Class\exists($type->getName())) {
                $resolved = $this->getContainer()->make($type->getName(), $arguments);

                if ($resolved === null && $type->allowsNull()) {
                    return null;
                }

                return $resolved;
            }

            return null;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                $resolved = $this->resolvedTypedValue($unionType, $arguments);

                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        return null;
    }
}