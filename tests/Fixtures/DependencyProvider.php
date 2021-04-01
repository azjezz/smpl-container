<?php

namespace Smpl\Container\Tests\Fixtures;

use Smpl\Container\Attributes\Resolves;
use Smpl\Container\Attributes\Shared;

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