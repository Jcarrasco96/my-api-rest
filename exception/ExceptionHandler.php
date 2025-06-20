<?php

namespace MAR\exception;

use ErrorException;
use JetBrains\PhpStorm\NoReturn;
use Throwable;

class ExceptionHandler
{

    #[NoReturn] public static function exceptionHandle(Throwable $th): void
    {
        if ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' && !headers_sent()) {
            header('HTTP/1.1 503 Service Unavailable');
        }

        $code = $th->getCode() == 0 ? 401 : $th->getCode();

        http_response_code($code);

        error_log(self::format($th));

        if (self::isJsonRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => $code,
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine()
            ]);
        } else {
            echo "<h1>ERROR!!!</h1>";
            echo "<p>{$th->getMessage()}</p>";
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