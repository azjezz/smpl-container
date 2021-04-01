<?php

namespace Smpl\Container\Tests\Fixtures;

use Smpl\Container\Attributes\Inject;

class SomeClass
{
    #[Inject]
    private ADependency $dependency;

    private AnotherDependency $anotherDependency;

    #[Inject]
    public function __construct(AnotherDependency $anotherDependency)
    {
        $this->anotherDependency = $anotherDependency;
    }

    public function getAnotherDependency(): AnotherDependency
    {
        return $this->anotherDependency;
    }

    public function getDependency(): ADependency
    {
        return $this->dependency;
    }
}