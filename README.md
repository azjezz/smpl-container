# SMPL Container

A simple, modern PHP container library.

## Installation

Install via composer using the following command

```
composer require smpl/container
```

## Usage

To use the SMPL container you must first get an instance of `Smpl\Container\Container`. This class is a singleton, so
you can get instance like so.

```php
use Smpl\Container\Container;

$container = Container::instance();
```

### Autowiring

Autowiring is enabled by default, so you can start using the container right away.

```php
use Smpl\Container\Container;
use Smpl\Container\Tests\Fixtures\SomeClass;

$container = Container::instance();
$someClass = $container->make(SomeClass::class);
```

To disable autowiring simply call the `disableAutowiring()` method. To re-enable it, call `enabledAutowiring()`.

```php
use Smpl\Container\Container;

$container = Container::instance();

// Disable the autowiring
$container->disableAutowiring();

// Enable to autowiring
$container->enableAutowiring();
```

The status of this is per instance of the container, so if you're using multiple instances you will need to call the
method on each of those in turn.

### Custom bindings

If you have a particular depdenency that can't be automatically created, you may tell the container how to do so using
the `bind` command.

```php
use Smpl\Container\Container;
use Smpl\Container\Tests\Fixtures\ADependency;use Smpl\Container\Tests\Fixtures\SomeClass;

$container = Container::instance();
$container->bind(ADependency::class, fn() => new ADependency(false), false);
```

The first parameter (`abstract`) is always the class you're binding, whether it's a concrete classname or an interface.

The second parameter (`concrete`) is the class to provide, or a way to get the class. There are currently three types of
support resolver.

The third parameter (`shared`) is whether a binding should be shared. Shared bindings are only resolved once, returning
the same instance for each successive request.

1. A class resolver. In this instance a class name is provided, and the container will automatically handle its
   dependencies.
2. A method resolver. For this an array of `[classname, methodname]` should be provided. If the method is not static the
   class will be resolved using a class resolver. Alternatively you may provide an object instead of a class name here.
3. A closure resolver. This is the same as the example above, using a provided closure to resolve the binding.

### Property injection

This container also supports injecting dependencies into properties before calling the constructor. To mark a property
for injection simply use the `Inject` attribute.

```php
use Smpl\Container\Attributes\Inject;
use Smpl\Container\Tests\Fixtures\ADependency;

class MyClass {
    #[Inject]
    protected ADependency $myProperty;
}
```

To use this injection you must be using typed properties. The container will use the type the same way it does for
method parameters.

### Providers

Rather than manually adding custom bindings to the container you can create a provider to contain them.

Unlike most other containers, a SMPL container provider is simple, and requires no interfaces. Instead a provide is a
class, with methods that are labelled with the `Resolves` attribute.

```php
use Smpl\Container\Attributes\Resolves;
use Smpl\Container\Attributes\Shared;
use Smpl\Container\Tests\Fixtures\ADependency;
use Smpl\Container\Tests\Fixtures\AnotherDependency;

class DependencyProvider
{
    #[Resolves, Shared]
    public static function provideADependency(): ADependency
    {
        return new ADependency(false);
    }

    #[Resolves]
    public function provideAnotherDependency(): AnotherDependency
    {
        return new AnotherDependency(true, 'yes');
    }
}
```

You can optionally add the `Shared` attribute to mark a binding as only needing to be resolved once. These methods
should either have a return type which will be used the `abstract` for binding, or provide it in the `Resolves`
attribute. If you add more than one type the first will be used as the primary binding, and all others will be added as
aliases.

These methods use the method resolver approach, so you can in turn have dependencies injected.

To register a provider you can simply called the `provider()` method on the container, with the provider class name.

```php
use Smpl\Container\Container;
use Smpl\Container\Tests\Fixtures\DependencyProvider;

$container = Container::instance();
$container->provider(DependencyProvider::class);
```

If a provider does not have a constructor, and all the resolution methods are static, no new instance of it will be
created.

If you wish to defer the registration of a provider you may add the `ProvidedBy` attribute to a class, which will point
the container to the provider when an attempt is made to resolve the dependency.

```php
use Smpl\Container\Attributes\ProvidedBy;
use Smpl\Container\Tests\Fixtures\DependencyProvider;

#[ProvidedBy(class: DependencyProvider::class)]
class ADependency
{
    private bool $foo;

    public function __construct(bool $foo)
    {
        $this->foo = $foo;
    }

    public function isFoo(): bool
    {
        return $this->foo;
    }
}
```
