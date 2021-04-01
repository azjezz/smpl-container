# SMPL Container

A simple, modern PHP container library.

## Installation

Install view composer using the following command

```
composer require smpl/container
```

## Usage

### Getting the container

You can create a new container using the standard object creation methods.

```php
use Smpl\Container\Container;

$container = new Container();
```

Though it is recommended that you use the `instance()` singleton method.

```php
use Smpl\Container\Container;

$container = Container::instance();
```

This method will ensure that you always receive the same instance of the container, as well as making sure it registers
itself with itself.

### Bindings

There are five types of bindings accepted by the container, all bound by calling the `bind()` method.

The signature of this method is:

```php
public function bind(string $abstract, Closure|string|array|null $concrete = null, bool $shared = false): static
```

The parameters are as follows.

| Parameter | Description |
| --- | --- |
|`abstract`| The class or interface the binding is for |
|`concrete`| The binding concrete, used to resolve the binding. Can be null to use `abstract` in its place|
|`shared`|If set to `true`, the binding will only be resolved once, with each subsequent call returning the same value.|

#### Binding a class

You can create a class binding in one of two ways, either by providing a classname or `null` for the `concrete`
parameter.

```php
use Smpl\Container\Container;
use Smpl\Container\Tests\Fixtures\ADependency;

Container::instance()->bind(ADependency::class);
Container::instance()->bind(ADependency::class, ADependency::class);
```

#### Binding a method

You can create a method binding by providing an array of `[class, method]` as the `concrete` parameter.

```php
use Smpl\Container\Container;
use Smpl\Container\Tests\Fixtures\ADependency;
use Smpl\Container\Tests\Fixtures\DependencyProvider;

Container::instance()->bind(ADependency::class, [DependencyProvider::class, 'providerADependency']);
```

#### Binding a closure

#### Binding a function

#### Binding an object

### Providers

Providers are classes that provide binding resolutions. All that is required for a class to be a provider, is for it to
have at least one method using the following attribute.

```
Smpl\Container\Attributes\Resolves
```

The resolver method should return an instance of the dependency it provides, and specify its type as either the methods
return type, or as the argument for the attribute.

```
Smpl\Container\Attributes\Resolves
```

An example using the return type:

```php
#[Resolves]
public static function resolveADependency() : ADependency
{
    return new ADependency(false);
}
```

An example using the attribute argument:

```php
#[Resolves(ADepdency::class)]
public static function resolveADependency()
{
    return new ADependency(false);
}
```

If all resolver methods are static, no new instance of the provider will be created. If either one or more methods are
not static, or there is a constructor present, an instance of the provider will be created before first resolving a
binding.

These methods are called the same way as any other binding, so your resolver methods can have parameters to be injected
automatically by the container.

Providers are registered with the container in one of two ways.

#### Manually registering a provider

To manually register a provider you should call the `provider()` method with the provider class name.

```php
use Smpl\Container\Container;
use Smpl\Container\Tests\Fixtures\DependencyProvider;

Container::instance()->provider(DependencyProvider::class);
```

#### Deferring registration

To defer registration of a provider you can add the following attribute to the classes that your provider provides.

```
Smpl\Container\Attributes\ProvidedBy
```

This attribute accepts one argument, `class`, which should be the fully qualified name of the provider class.

Once an instance of a class using this attribute is requested, the provider will be automatically registered, including
all other bindings contained within it.

```php
use Smpl\Container\Container;
use Smpl\Container\Tests\Fixtures\DependencyProvider;
use Smpl\Container\Attributes\ProvidedBy;

#[ProvidedBy(DependencyProvider::class)]
class MyClass {
}

$myClass = Container::instance()->make(MyClass::class);
```

#### Aliasing bindings

To alias a binding you can provide the aliases as arguments on the `Resolves`
attribute. The `classes` parameter for the attribute is a variable parameter of type `string`.

When using aliases the first value on the attribute will be used as the core binding that others are aliased to so you
will need to specify the type in the attribute regardless of whether the method has a return type or not.

#### Sharing bindings

If you wish for a binding to only be resolved once, you can mark it as shared by using the following attribute on the
resolver method:

```
Smpl\Container\Attributes\Shared
```