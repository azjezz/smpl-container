<?php

namespace Smpl\Container;

use Psl\Str;
use Psl\Iter;
use Psl\Dict;
use Psl\Vec;
use Psl\Class;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Smpl\Container\Attributes\Resolves;
use Smpl\Container\Attributes\Shared;
use Smpl\Container\Exceptions\InvalidProvider;

class Provider
{
    private string $provider;

    private ReflectionClass $reflection;

    private bool $requiresInstance = false;

    private array $provides = [];

    private array $sharedProviders = [];

    /**
     * ProviderHandler constructor.
     *
     * @param string $provider
     */
    public function __construct(string $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param \Smpl\Container\Container $container
     *
     * @throws \Smpl\Container\Exceptions\InvalidProvider
     */
    public function process(Container $container): void
    {
        $this->validateProvider();
        $this->processConstructor();
        $this->processProviders();

        if ($this->requiresInstance) {
            $container->bind($this->provider, null, true);
        }

        foreach ($this->provides as $method => $abstracts) {
            /** @var string $abstract */
            $abstract = Iter\first($abstracts);
            $aliases = Vec\values(Dict\drop($abstracts, 1));

            $container->bind($abstract, [$this->provider, $method], Iter\contains($this->sharedProviders, $method))
                      ->alias($abstract, ...$aliases);
        }
    }

    private function processConstructor(): void
    {
        $constructor = $this->reflect()->getConstructor();

        if ($constructor === null) {
            $this->requiresInstance = false;
        } else {
            $this->requiresInstance = true;
        }
    }

    private function processProviderMethod(ReflectionMethod $providingMethod): void
    {
        $attributes = $providingMethod->getAttributes(Resolves::class);

        foreach ($attributes as $attribute) {
            $this->processProviderMethodProviding($providingMethod, $attribute);
        }
    }

    private function processProviderMethodProviding(ReflectionMethod $providingMethod, ReflectionAttribute $providesAttribute): void
    {
        $arguments = $providesAttribute->getArguments();
        $provides  = $arguments['provides'] ?? [];

        if (! $providingMethod->isStatic()) {
            $this->requiresInstance = true;
        }

        if (Iter\is_empty($provides)) {
            if (! $providingMethod->hasReturnType()) {
                throw new InvalidProvider(Str\format(
                    'Provider %s::%s does not specify what it provides',
                    $this->provider,
                    $providingMethod->getName()
                ));
            }

            $returnType = $providingMethod->getReturnType();

            if ($returnType instanceof ReflectionUnionType) {
                $provides = Dict\map($returnType->getTypes(), static fn(ReflectionNamedType $type) => $type->getName());
            } else {
                $provides = [$returnType->getName()];
            }
        }

        if (Iter\is_empty($provides)) {
            throw new InvalidProvider(Str\format(
                'Provider %s::%s does not specify what it provides',
                $this->provider,
                $providingMethod->getName()
            ));
        }

        $this->provides[$providingMethod->getShortName()] = $provides;

        if (! Iter\is_empty($providingMethod->getAttributes(Shared::class))) {
            $this->sharedProviders[] = $providingMethod->getShortName();
        }
    }

    private function processProviders(): void
    {
        $providingMethods = Dict\filter(
            $this->reflect()->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method) => ! Iter\is_empty($method->getAttributes(Resolves::class))
        );

        if (Iter\is_empty($providingMethods)) {
            throw new InvalidProvider(Str\format('Provider %s provides nothing', $this->provider));
        }

        foreach ($providingMethods as $providingMethod) {
            $this->processProviderMethod($providingMethod);
        }
    }

    /**
     * @return \ReflectionClass
     * @throws \ReflectionException
     */
    private function reflect(): ReflectionClass
    {
        if (! isset($this->reflection)) {
            $this->reflection = new ReflectionClass($this->provider);
        }

        return $this->reflection;
    }

    /**
     * @throws \Smpl\Container\Exceptions\InvalidProvider
     */
    private function validateProvider(): void
    {
        if (!Class\exists($this->provider)) {
            throw new InvalidProvider(Str\format('Invalid provider class %s', $this->provider));
        }
    }
}