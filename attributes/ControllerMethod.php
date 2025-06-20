<?php

namespace MAR\attributes;

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

    public static function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === ControllerMethod::ROUTER_POST;
    }

    public static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

}