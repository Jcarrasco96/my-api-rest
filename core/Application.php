<?php

namespace MyApiRest\core;

use JetBrains\PhpStorm\NoReturn;
use MyApiRest\helpers\Utilities;
use MyApiRest\services\Language;
use MyApiRest\services\Logger;

class Application
{

    public static array $config = [
        'name' => 'MyApiRestApp',
        'version' => '1.0.0-dev',
        'language' => 'en',
        'timezone' => 'America/Havana',
    ];

    public static Logger $logger;
    public static Language $language;

    private float|string $time_start;

    public function __construct($config = [])
    {
        $this->time_start = microtime(true);

        define('LIBRARY_ROOT', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
        define('LIBRARY_LANGUAGES', LIBRARY_ROOT . 'languages' . DIRECTORY_SEPARATOR);

        define('ROOT', getcwd() . DIRECTORY_SEPARATOR);
        define('APP_VIEWS', ROOT . 'views' . DIRECTORY_SEPARATOR);
        define('APP_RUNTIME', ROOT . 'runtime' . DIRECTORY_SEPARATOR);
        define('APP_CONTROLLERS', ROOT . 'controllers' . DIRECTORY_SEPARATOR);

        self::$config = array_merge(self::$config, $config);

        date_default_timezone_set(self::$config['timezone']);

        self::$logger = new Logger(APP_RUNTIME . 'logs' . DIRECTORY_SEPARATOR . 'app.log');
        self::$language = new Language(self::$config['language']);
    }

    #[NoReturn] public function run(): void
    {
        $segments = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

        if (empty($segments) || empty($segments[0])) {
            $segments[0] = 'v1';
            $segments[1] = 'site';
            $segments[2] = 'index';
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

        (new $controller_name)->createAction($segments[2], array_slice($segments, 3));

        $this->dispose();
        exit();
    }

    public static function t(string $key, array $params = []): string
    {
        return self::$language->t($key, $params);
    }

    private function dispose(): void
    {
        $mPeak = Utilities::filesize(memory_get_peak_usage(true));
        $mUsage = Utilities::filesize(memory_get_usage(true));

        $execTime = number_format(microtime(true) - $this->time_start, 4);

        self::$logger->notice("SCRIPT REAL EXECUTION TIME: {$execTime}s, MEM PEAK USAGE: $mPeak, USAGE: $mUsage");
    }

}