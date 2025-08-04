<?php

namespace SimpleApiRest\core;

/**
 *
 * Just add a robots.txt file with this content
 * ```
 * User-agent: *
 * Disallow: /antibots
 * ```
 *
 * in addition to correctly configuring the .htaccess
 */
class Antibots
{

    public static function isIpBlocked($ip, $file): bool
    {
        if (file_exists($file)) {
            $blockedIps = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return in_array($ip, $blockedIps);
        }
        return false;
    }

    public static function blockIp($ip, $file): void
    {
        file_put_contents($file, $ip . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function isBot(): bool
    {
        $bots = ['Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'YandexBot', 'facebot', 'ia_archiver'];

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

}