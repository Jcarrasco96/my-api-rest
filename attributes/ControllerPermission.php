<?php

namespace MAR\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ControllerPermission
{

    public function __construct(public array $permissions)
    {
    }

}