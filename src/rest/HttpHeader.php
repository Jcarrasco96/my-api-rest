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
        //header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
        //header("Access-Control-Allow-Credentials: true");

        // Basic protections
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");

        // Only allow HTTPS (HSTS)
        header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");

        // Content Policy (adjust according to your frontend)
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none';");

        // Disable cache (for sensitive APIs)
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        // Referee policy
        header("Referrer-Policy: no-referrer");
    }

}