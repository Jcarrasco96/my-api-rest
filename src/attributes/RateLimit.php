<?php

namespace SimpleApiRest\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RateLimit
{

    public function __construct(public int $limit = 5, public int $seconds = 60)
    {
    }

}