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

        $data = [
            'message' => $th->getMessage(),
            'status' => $code,
        ];

        if (APP_ENV === 'dev') {
            $data['file'] = $th->getFile() . '(' . $th->getLine() . ')';
            $data['trace'] = $th->getTraceAsString();
        }

        Application::$logger->error("ERROR " . json_encode($data, JSON_PRETTY_PRINT));

        echo json_encode(JsonResponse::response($data), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
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

}