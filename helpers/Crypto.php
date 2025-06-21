<?php

namespace MyApiRest\helpers;

use Random\RandomException;

readonly class Crypto
{

    /**
     * @throws RandomException
     */
    public static function encrypt(string $data, string $key, string $method = 'aes-256-cbc'): array
    {
        $iv = bin2hex(random_bytes(8));

        return [
            'data' => base64_encode(openssl_encrypt($data, $method, $key, 0, $iv)),
            'iv' => base64_encode($iv),
        ];
    }

    public static function decrypt(string $data, string $iv, string $key, string $method = 'aes-256-cbc'): string
    {
        return openssl_decrypt(base64_decode($data), $method, $key, 0, base64_decode($iv));
    }

}