<?php

namespace Smpl\Container\Tests;

use PHPUnit\Framework\TestCase;
use Smpl\Container\Container;
use Smpl\Container\Resolvers\ClosureResolver;
use Smpl\Container\Tests\Fixtures\ADependency;

class BindingTests extends TestCase
{
    /**
     * @test
     */
    public function correctlyBindsClosureResolvers(): void
    {
        $container = new Container;
        $container->bind(ADependency::class, fn() => new ADependency(false));

        $resolver = $container->resolver(ADependency::class);

        self::assertNotNull($resolver);
        self::assertInstanceOf(ClosureResolver::class, $resolver);

        $dependency = $container->make(ADependency::class);

        self::assertNotNull($dependency);
        self::assertInstanceOf(ADependency::class, $dependency);
    }

    public function returnsDifferentInstanceWhenDependencyMarkedAsSharedAndFreshInstanceRequested(): void
    {
        $container = new Container;
        $container->bind(ADependency::class, fn() => new ADependency(false), true);

        $dependency1 = $container->make(ADependency::class);
        $dependency2 = $container->make(ADependency::class, fresh: true);

        self::assertNotNull($dependency1);
        self::assertInstanceOf(ADependency::class, $dependency1);
        self::assertNotNull($dependency2);
        self::assertInstanceOf(ADependency::class, $dependency2);
        self::assertNotSame($dependency1, $dependency2);
    }

    public function returnsDifferentInstanceWhenDependencyNotMarkedAsShared(): void
    {
        $container = new Container;
        $container->bind(ADependency::class, fn() => new ADependency(false), false);

        $dependency1 = $container->make(ADependency::class);
        $dependency2 = $container->make(ADependency::class);

        self::assertNotNull($dependency1);
        self::assertInstanceOf(ADependency::class, $dependency1);
        self::assertNotNull($dependency2);
        self::assertInstanceOf(ADependency::class, $dependency2);
        self::assertNotSame($dependency1, $dependency2);
    }

    public function returnsSameInstanceWhenDependencyMarkedAsShared(): void
    {
        $container = new Container;
        $container->bind(ADependency::class, fn() => new ADependency(false), true);

        $dependency1 = $container->make(ADependency::class);
        $dependency2 = $container->make(ADependency::class);

        self::assertNotNull($dependency1);
        self::assertInstanceOf(ADependency::class, $dependency1);
        self::assertNotNull($dependency2);
        self::assertInstanceOf(ADependency::class, $dependency2);
        self::assertSame($dependency1, $dependency2);
    }
}