<?php

namespace SimpleApiRest\rest;

use JetBrains\PhpStorm\NoReturn;
use ReflectionException;
use SimpleApiRest\core\BaseApplication;
use SimpleApiRest\core\Utilities;
use SimpleApiRest\exceptions\BadRequestHttpException;
use SimpleApiRest\exceptions\MethodNotAllowedHttpException;
use SimpleApiRest\exceptions\RequestEntityTooLargeHttpException;
use SimpleApiRest\exceptions\ServerErrorHttpException;
use SimpleApiRest\exceptions\UnsupportedMediaTypeHttpException;

class Rest extends BaseApplication
{

    public Router $router;

    /**
     * @throws BadRequestHttpException
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        define('RATE_LIMIT_FOLDER', APP_RUNTIME . 'rate_limit' . DIRECTORY_SEPARATOR);

        if (!is_dir(RATE_LIMIT_FOLDER)) {
            mkdir(RATE_LIMIT_FOLDER, 0755, true);
        }

        $this->router = new Router();
    }

    /**
     * @throws UnsupportedMediaTypeHttpException
     * @throws BadRequestHttpException
     * @throws ReflectionException
     * @throws RequestEntityTooLargeHttpException
     * @throws MethodNotAllowedHttpException
     * @throws ServerErrorHttpException
     */
    #[NoReturn]
    public function run(): void
    {
        $this->beforeInit();

        $this->router->loadRoutes();

        $data = $this->router->resolve();

        if (empty($data['message'])) {
            $data['message'] = 'No message provided';
        }

        $json = JsonResponse::response($data);

        $execTime = number_format(microtime(true) - $this->time_start, 5);

        if (APP_ENV === 'dev') {
            $json = array_merge($json, [
                'execTime' => $execTime,
            ]);
        }

        echo json_encode($json, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

        $this->dispose($execTime);
    }

    protected function beforeInit(): void
    {
        HttpHeader::setDefaultHeaders(Rest::$config['origins']);

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit(0);
        }
    }

    protected function dispose(float $execTime): void
    {
        $mPeak = Utilities::filesize(memory_get_peak_usage(true));
        $mUsage = Utilities::filesize(memory_get_usage(true));

        self::$logger->notice("SCRIPT REAL TIME EXECUTION: {$execTime}s, MEMORY PEAK USAGE: $mPeak, MEMORY USAGE: $mUsage");
    }

}