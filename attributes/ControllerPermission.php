<?php

namespace MyApiRest\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ControllerPermission
{

    public function __construct(public array $permissions)
    {
    }

}