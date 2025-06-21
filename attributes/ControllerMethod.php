<?php

namespace MyApiRest\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ControllerMethod
{

    const ROUTER_DELETE = 'DELETE';
    const ROUTER_GET = 'GET';
    const ROUTER_HEAD = 'HEAD';
    const ROUTER_OPTIONS = 'OPTIONS';
    const ROUTER_PATCH = 'PATCH';
    const ROUTER_POST = 'POST';
    const ROUTER_PUT = 'PUT';

    public function __construct(public array $methods)
    {
    }

}