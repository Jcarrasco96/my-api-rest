<?php

namespace MAR\helpers;

use Random\RandomException;

readonly class Crypto
{

    public function __construct(private string $key, private string $method = 'aes-256-cbc')
    {
    }

    /**
     * @throws RandomException
     */
    public function encrypt(string $data): array
    {
        $iv = bin2hex(random_bytes(8));

        return [
            'data' => base64_encode(openssl_encrypt($data, $this->method, $this->key, 0, $iv)),
            'iv' => base64_encode($iv),
        ];
    }

    public function decrypt(string $data, string $iv): string
    {
        return openssl_decrypt(base64_decode($data), $this->method, $this->key, 0, base64_decode($iv));
    }

}