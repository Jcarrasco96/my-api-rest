<?php

namespace MyApiRest\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ControllerRateLimit
{

    public function __construct(public int $limit = 5, public int $seconds = 60)
    {
    }

}