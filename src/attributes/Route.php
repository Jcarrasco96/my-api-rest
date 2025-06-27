<?php

namespace SimpleApiRest\attributes;

use Attribute;
use SimpleApiRest\exceptions\ServerErrorHttpException;

#[Attribute(Attribute::TARGET_METHOD)]
class Route {

    const ROUTER_DELETE = 'DELETE';
    const ROUTER_GET = 'GET';
    const ROUTER_HEAD = 'HEAD';
    const ROUTER_OPTIONS = 'OPTIONS';
    const ROUTER_PATCH = 'PATCH';
    const ROUTER_POST = 'POST';
    const ROUTER_PUT = 'PUT';

    /**
     * @throws ServerErrorHttpException
     */
    public function __construct(public string $path, public array $methods = [self::ROUTER_GET], public string $version = 'v1') {

        if (empty($this->methods)) {
            throw new ServerErrorHttpException('Method can not be empty.');
        }

        $invalidMethods = array_diff($this->methods, [
            self::ROUTER_DELETE,
            self::ROUTER_GET,
            self::ROUTER_HEAD,
            self::ROUTER_OPTIONS,
            self::ROUTER_PATCH,
            self::ROUTER_POST,
            self::ROUTER_PUT,
        ]);

        if (!empty($invalidMethods)) {
            throw new ServerErrorHttpException('Invalid HTTP method(s): ' . implode(', ', $invalidMethods));
        }

    }

}
