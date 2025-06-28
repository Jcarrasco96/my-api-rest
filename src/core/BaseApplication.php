<?php

namespace SimpleApiRest\core;

use SimpleApiRest\exceptions\BadRequestHttpException;

abstract class BaseApplication
{

    public static array $config = [
        'name' => 'SimpleApiRestApp',
        'version' => '1.0.6-dev',
        'language' => 'en',
        'timezone' => 'America/Havana',
    ];

    public static Logger $logger;
    public static Language $language;

    protected float|string $time_start;

    /**
     * @throws BadRequestHttpException
     */
    public function __construct($config = [])
    {
        $this->time_start = microtime(true);

        defined('APP_ENV') or define('APP_ENV', 'prod');

        self::$config = array_merge(self::$config, $config);

        define('LIBRARY_ROOT', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
        define('LIBRARY_LANGUAGES', LIBRARY_ROOT . 'languages' . DIRECTORY_SEPARATOR);

        define('APP_ROOT', getcwd() . DIRECTORY_SEPARATOR);
        define('APP_RUNTIME', APP_ROOT . 'runtime' . DIRECTORY_SEPARATOR);
        define('APP_CONTROLLERS_FOLDER', APP_ROOT . 'controllers' . DIRECTORY_SEPARATOR);
        define('APP_MODELS_FOLDER', APP_ROOT . 'models' . DIRECTORY_SEPARATOR);
        define('APP_REPOSITORY_FOLDER', APP_ROOT . 'repository' . DIRECTORY_SEPARATOR);

        define('APP_LOGS_FOLDER', APP_RUNTIME . 'logs' . DIRECTORY_SEPARATOR);

        self::$logger = new Logger(APP_LOGS_FOLDER . 'app.log');

        ExceptionHandler::register();

        if (!is_dir(APP_LOGS_FOLDER)) {
            mkdir(APP_LOGS_FOLDER, 0755, true);
        }
        if (!is_dir(APP_CONTROLLERS_FOLDER)) {
            mkdir(APP_CONTROLLERS_FOLDER, 0755, true);
        }
        if (!is_dir(APP_MODELS_FOLDER)) {
            mkdir(APP_MODELS_FOLDER, 0755, true);
        }
        if (!is_dir(APP_REPOSITORY_FOLDER)) {
            mkdir(APP_REPOSITORY_FOLDER, 0755, true);
        }

        if (empty(self::$config['controllerNamespace'])) {
            throw new BadRequestHttpException('Controller Namespace is missing');
        }
        if (empty(self::$config['jwtSecretKey'])) {
            throw new BadRequestHttpException('JWT Secret Key is missing');
        }
        if (empty(self::$config['userModel'])) {
            throw new BadRequestHttpException('User Model is missing');
        }

        date_default_timezone_set(self::$config['timezone']);

        self::$language = new Language(self::$config['language']);
    }

    abstract protected function beforeInit(): void;

    abstract protected function dispose(float $execTime): void;

    public static function t(string $key, array $params = []): string
    {
        return self::$language->t($key, $params);
    }

}