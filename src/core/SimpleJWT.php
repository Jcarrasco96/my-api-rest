<?php

namespace SimpleApiRest\core;

class SimpleJWT
{

    public static function create($payload, $expSeconds = 900): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload['exp'] = time() + $expSeconds;
        $payload['iat'] = time();

        $base64UrlHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", BaseApplication::$config['jwtSecretKey'], true);
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    public static function verify($jwt): array|false
    {
        [$header, $payload, $signature] = explode('.', $jwt);

        $expected_signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payload", BaseApplication::$config['jwtSecretKey'], true)), '+/', '-_'), '=');

        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }

        $payloadData = json_decode(base64_decode($payload), true);

        return ($payloadData['exp'] ?? 0) > time() ? $payloadData : false;
    }

}