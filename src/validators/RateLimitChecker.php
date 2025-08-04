<?php

namespace SimpleApiRest\validators;

use SimpleApiRest\core\Utilities;
use SimpleApiRest\exceptions\TooManyRequestsHttpException;

class RateLimitChecker
{

    /**
     * @throws TooManyRequestsHttpException
     */
    public static function check(string $key, int $limit, int $seconds): void {
        $clientId = hash('sha256', Utilities::getIp());

        $path = RATE_LIMIT_FOLDER . "rate_limit_$key.$clientId.json";

        $now = time();

        if (!file_exists($path)) {
            file_put_contents($path, json_encode(['count' => 1, 'timestamp' => time()]));
            self::setHeaders($limit, $limit - 1, $now + $seconds);
            return;
        }

        $data = json_decode(file_get_contents($path), true);

        if ($now - $data['timestamp'] > $seconds) {
            file_put_contents($path, json_encode(['count' => 1, 'timestamp' => $now]));
            self::setHeaders($limit, $limit - 1, $now + $seconds);
            return;
        }

        if ($data['count'] >= $limit) {
            header('Retry-After: ' . $seconds);
            self::setHeaders($limit, 0, $data['timestamp'] + $seconds);
            throw new TooManyRequestsHttpException('Rate limit exceeded');
        }

        $data['count']++;
        file_put_contents($path, json_encode($data));
        self::setHeaders($limit, $limit - $data['count'], $data['timestamp'] + $seconds);
    }

    private static function setHeaders($limit, $remaining, $reset): void
    {
        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $reset);
    }

}