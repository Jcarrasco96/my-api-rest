<?php

namespace SimpleApiRest\core;

use ErrorException;
use JetBrains\PhpStorm\NoReturn;
use SimpleApiRest\console\CLI;
use SimpleApiRest\rest\JsonResponse;
use Throwable;

class ExceptionHandler
{

    #[NoReturn] public static function exceptionHandle(Throwable $th): void
    {
        if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' && !headers_sent()) {
            header('HTTP/1.1 503 Service Unavailable');
        }

        $code = $th->getCode() == 0 || !is_int($th->getCode()) ? 401 : $th->getCode();

        $data = [
            'message' => $th->getMessage(),
            'status' => $code,
        ];

        if (APP_ENV === 'dev') {
            $data['file'] = $th->getFile() . '(' . $th->getLine() . ')';
            $data['trace'] = $th->getTraceAsString();
        }

        BaseApplication::$logger->error("ERROR " . json_encode($data, JSON_PRETTY_PRINT));

        if (self::isJsonRequest()) {
            echo json_encode(JsonResponse::response($data), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        } else {
            echo CLI::clog("ERROR!!! " . $th->getMessage(), 'r') . PHP_EOL;
            echo self::format($th) . PHP_EOL;
        }
    }

    public static function register(): void
    {
        set_exception_handler([self::class, 'exceptionHandle']);
        set_error_handler([self::class, 'errorHandle']);
    }

    /**
     * @throws ErrorException
     */
    public static function errorHandle(int $severity, string $message, string $file, int $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    private static function isJsonRequest(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
            || isset($_GET['json']);
    }

    private static function format(Throwable $e): string
    {
        return sprintf("[%s] %s in %s:%d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }

}