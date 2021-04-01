<?php

namespace Smpl\Container;

use Closure;

/**
 * @return \Smpl\Container\Container
 */
function container(): Container
{
    return Container::instance();
}

/**
 * @param string                     $abstract
 * @param \Closure|string|array|null $concrete
 * @param bool                       $shared
 *
 * @return \Smpl\Container\Container
 */
function bind(string $abstract, Closure|string|array|null $concrete = null, bool $shared = false): Container
{
    return container()->bind($abstract, $concrete, $shared);
}

/**
 * @param string $class
 * @param array  $arguments
 * @param bool   $fresh
 * @param bool   $shared
 *
 * @return object|null
 */
function make(string $class, array $arguments = [], bool $fresh = false, bool $shared = false): ?object
{
    return container()->make($class, $arguments, $fresh, $shared);
}