<?php

namespace MyApiRest\core;

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

        $code = $th->getCode() == 0 || !is_int($th->getCode()) ? 401 : $th->getCode();

        http_response_code($code);

        $data = [
            'status'  => $code,
            'message' => $th->getMessage()
        ];

        if (self::isJsonRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data);
        } else {
            echo "<h1>ERROR!!!</h1>";
            echo "<p>{$th->getMessage()}</p>";
        }

        Application::$logger->error("ERROR " . json_encode([
            ...$data,
            'file' => $th->getFile() . '(' . $th->getLine() . ')',
            'trace' => $th->getTraceAsString(),
        ], JSON_PRETTY_PRINT));
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

}