<?php

namespace Smpl\Container\Tests;

use PHPUnit\Framework\TestCase;
use Smpl\Container\Container;
use Smpl\Container\Tests\Fixtures\ADependency;
use Smpl\Container\Tests\Fixtures\AnotherDependency;
use Smpl\Container\Tests\Fixtures\DependencyProvider;

class ProviderTests extends TestCase
{
    /**
     * @test
     */
    public function canAutomaticallyRegisterProviders(): void
    {
        $container   = new Container;
        $aDependency = $container->make(ADependency::class);

        self::assertTrue($container->hasProvider(DependencyProvider::class));
        self::assertTrue($container->hasBinding(ADependency::class));
        self::assertTrue($container->hasBinding(AnotherDependency::class));
        self::assertNotNull($aDependency);
        self::assertInstanceOf(ADependency::class, $aDependency);
    }

    /**
     * @test
     */
    public function canManuallyRegisterProviders(): void
    {
        $container = new Container;
        $container->provider(DependencyProvider::class);

        self::assertTrue($container->hasProvider(DependencyProvider::class));
        self::assertTrue($container->hasBinding(ADependency::class));
        self::assertTrue($container->hasBinding(AnotherDependency::class));
    }

    /**
     * @test
     */
    public function correctlyCreatesBindingsFromProviders(): void
    {
        $container = new Container;
        $container->provider(DependencyProvider::class);

        $aDependency = $container->make(ADependency::class);

        self::assertNotNull($aDependency);
        self::assertInstanceOf(ADependency::class, $aDependency);

        $anotherDependency = $container->make(AnotherDependency::class);

        self::assertNotNull($anotherDependency);
        self::assertInstanceOf(AnotherDependency::class, $anotherDependency);
    }
}