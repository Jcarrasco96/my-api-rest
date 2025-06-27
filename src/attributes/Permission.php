<?php

namespace SimpleApiRest\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Permission
{

    public function __construct(public array $permissions)
    {
    }

}