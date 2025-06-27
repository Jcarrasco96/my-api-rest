<?php

namespace SimpleApiRest\rest;

class HttpHeader
{

    public static function setDefaultHeaders(array $allowedOrigins): void
    {

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header("Access-Control-Allow-Origin: " . $allowedOrigins[0]);
        }

        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
    }

}