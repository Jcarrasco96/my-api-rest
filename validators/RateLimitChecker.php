<?php

namespace MyApiRest\validators;

use MyApiRest\exceptions\TooManyRequestsHttpException;

class RateLimitChecker
{

    /**
     * @throws TooManyRequestsHttpException
     */
    public static function check(string $key, int $limit, int $seconds): void {
        $clientId = self::clientIdentifier();

        $path = RATE_LIMIT_FOLDER . "rate_limit_$key.$clientId.json";

        if (!file_exists($path)) {
            file_put_contents($path, json_encode(['count' => 1, 'timestamp' => time()]));
            return;
        }

        $data = json_decode(file_get_contents($path), true);
        $now = time();

        if ($now - $data['timestamp'] > $seconds) {
            file_put_contents($path, json_encode(['count' => 1, 'timestamp' => $now]));
            return;
        }

        if ($data['count'] >= $limit) {
            header('Retry-After: ' . $seconds);
            throw new TooManyRequestsHttpException('Rate limit exceeded');
        }

        $data['count']++;
        file_put_contents($path, json_encode($data));
    }

    private static function clientIdentifier(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

}