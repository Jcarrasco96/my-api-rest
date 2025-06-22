<?php

namespace SimpleApiRest\core;

use JetBrains\PhpStorm\NoReturn;
use SimpleApiRest\exceptions\BadRequestHttpException;
use SimpleApiRest\helpers\Utilities;
use SimpleApiRest\services\Language;
use SimpleApiRest\services\Logger;

class Application
{

    public static array $config = [
        'name' => 'SimpleApiRestApp',
        'version' => '1.0.4-dev',
        'language' => 'en',
        'timezone' => 'America/Havana',
    ];

    public static Logger $logger;
    public static Language $language;

    private float|string $time_start;

    /**
     * @throws BadRequestHttpException
     */
    public function __construct($config = [])
    {
        $this->time_start = microtime(true);

        defined('APP_ENV') or define('APP_ENV', 'prod');

        ExceptionHandler::register();

        define('LIBRARY_ROOT', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
        define('LIBRARY_LANGUAGES', LIBRARY_ROOT . 'languages' . DIRECTORY_SEPARATOR);

        define('APP_ROOT', getcwd() . DIRECTORY_SEPARATOR);
        define('APP_VIEWS', APP_ROOT . 'views' . DIRECTORY_SEPARATOR);
        define('APP_RUNTIME', APP_ROOT . 'runtime' . DIRECTORY_SEPARATOR);
        define('APP_CONTROLLERS', APP_ROOT . 'controllers' . DIRECTORY_SEPARATOR);

        define('RATE_LIMIT_FOLDER', APP_RUNTIME . 'rate_limit' . DIRECTORY_SEPARATOR);
        define('LOGS_FOLDER', APP_RUNTIME . 'logs' . DIRECTORY_SEPARATOR);

        if (!is_dir(RATE_LIMIT_FOLDER)) {
            mkdir(RATE_LIMIT_FOLDER, 0755, true);
        }

        if (!is_dir(LOGS_FOLDER)) {
            mkdir(LOGS_FOLDER, 0755, true);
        }

        self::$config = array_merge(self::$config, $config);

        date_default_timezone_set(self::$config['timezone']);

        self::$logger = new Logger(LOGS_FOLDER . 'app.log');
        self::$language = new Language(self::$config['language']);

        if (empty(self::$config['jwtSecretKey'])) {
            throw new BadRequestHttpException('JWT Secret Key is missing');
        }
        if (empty(self::$config['controllerNamespace'])) {
            throw new BadRequestHttpException('Controller Namespace is missing');
        }
        if (empty(self::$config['userModel'])) {
            throw new BadRequestHttpException('User Model is missing');
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    #[NoReturn] public function run(): void
    {
        $this->beforeInit();

        $segments = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

        if (empty($segments) || empty($segments[0]) || empty($segments[1]) || empty($segments[2])) {
            throw new BadRequestHttpException('URL not found.');
        }

//        $g = [];
//
//        if (!empty($_GET)) {
//            $g = array_values($_GET);
//        }
//
//        echo json_encode([
//            'segments' => $segments,
//            'get' => $_GET,
//            'slice' => array_slice($segments, 2),
//            'g' => $g,
//            'slicePlusG' => array_merge(array_slice($segments, 2), $g)
//        ], JSON_PRETTY_PRINT);
//        die();

        $controller_name = self::$config['controllerNamespace'] . $segments[0] . '\\' . ucfirst($segments[1]) . 'Controller';

        $data = (new $controller_name)->createAction($segments[2], array_slice($segments, 3));

        if (empty($data['message'])) {
            throw new BadRequestHttpException('Message must be provided.');
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

    public static function t(string $key, array $params = []): string
    {
        return self::$language->t($key, $params);
    }

    private function dispose(float $execTime): void
    {
        $mPeak = Utilities::filesize(memory_get_peak_usage(true));
        $mUsage = Utilities::filesize(memory_get_usage(true));

        self::$logger->notice("SCRIPT REAL TIME EXECUTION: {$execTime}s, MEMORY PEAK USAGE: $mPeak, MEMORY USAGE: $mUsage");
    }

    private function beforeInit(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        if (in_array($origin, Application::$config['origins'])) {
            header("Access-Control-Allow-Origin: $origin");
        }

        $allowedHeaders = ['Content-Type', 'Authorization', 'X-CSRF-Token'];

        header("Access-Control-Allow-Headers: " . implode(',', $allowedHeaders));
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 3600");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }

}