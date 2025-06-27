<?php

namespace SimpleApiRest\console;

use Ramsey\Uuid\Uuid;
use SimpleApiRest\core\BaseApplication;
use SimpleApiRest\core\Utilities;
use SimpleApiRest\exceptions\ServerErrorHttpException;

class CLI extends BaseApplication
{

    public function __construct($config = [])
    {
        parent::__construct($config);

        define('PHP_TAB', "\t");
    }

    public function run(array $argv): void
    {
        $this->beforeInit();

        $file = array_shift($argv);

        $command = $argv[0] ?? null;

        if (!$command) {
            $this->showHelp($file);
            exit(0);
        }

        switch ($command) {
            case 'g':
            case 'generate':
                $generate = $argv[1] ?? null;
                $override = isset($argv[2]) && $argv[2] === '-fc';

                switch ($generate) {
                    case 'm':
                    case 'models':
                        CLIModel::generate($override);
                        break;

                    case 'r':
                    case 'repositories':
                        CLIRepository::generate($override);
                        break;

                    case 'c':
                    case 'controllers':
                        CLIController::generate($override);
                        break;

                    default:
                        echo self::clog("UNKNOWN COMMAND g $generate", 'r') . PHP_EOL;
                        exit(1);
                }
                break;

            case 'serve':
                exec("php -S localhost:8080");
                break;

            case 'help':
                $this->showHelp($file);
                break;

            case 'config':
                echo "Config database:" . PHP_EOL;

                $baseNamespace = self::read("Type main namespace: ", required: true);

                $host = self::read("Type host (default: localhost): ", 'localhost', true);
                $port = self::read("Type port (default: 3306): ", '3306', true);
                $username = self::read("Type username (default: root): ", 'root', true);
                $password = self::read("Type password: ");
                $db_name = self::read("Type database name (default: my-api-rest-api): ", 'my-api-rest-api', true);

                $example = file_get_contents(__DIR__ . '/_example.config.php');

                $exampleOK = str_replace([
                    '{{jwtSecretKey}}',
                    '{{driver}}',
                    '{{host}}', '{{port}}', '{{username}}', '{{password}}', '{{database}}',
                    '{{controllerNamespace}}',
                    '{{modelNamespace}}',
                    '{{repositoryNamespace}}',
                    '\'{{userModel}}\'',
                ],
                [
                    Uuid::uuid4()->toString(),
                    'mysql',
                    $host, $port, $username, $password, $db_name,
                    $baseNamespace . '\\\\controllers',
                    $baseNamespace . '\\\\models',
                    $baseNamespace . '\\\\repository',
                    $baseNamespace . '\\models\\User::class',
                ],
                $example);

                file_put_contents(__DIR__ . '/_config.php', $exampleOK);
                break;

            case 'migrate':
                if (BaseApplication::$config['db']['driver'] !== 'mysql') {
                    echo self::clog("This feature is not supported yet.", 'y') . PHP_EOL;
                    exit(1);
                }

                $authenticationMigration = new AuthenticationMigration();
                try {
                    $authenticationMigration->migrate();
                } catch (ServerErrorHttpException $e) {
                    echo self::clog($e->getMessage(), 'r') . PHP_EOL;
                }
                break;
        }

        $execTime = number_format(microtime(true) - $this->time_start, 5);

        $this->dispose($execTime);
    }

    protected function dispose(float $execTime): void
    {
        $mPeak = Utilities::filesize(memory_get_peak_usage(true));
        $mUsage = Utilities::filesize(memory_get_usage(true));

        self::$logger->notice("CONSOLE SCRIPT REAL TIME EXECUTION: {$execTime}s, MEMORY PEAK USAGE: $mPeak, MEMORY USAGE: $mUsage");

        echo PHP_EOL . "REAL TIME EXECUTION: " . self::clog($execTime . " seconds", 'c') . PHP_EOL;
        echo "MEMORY PEAK USAGE: " . self::clog($mPeak, 'c') . PHP_EOL;
        echo "MEMORY USAGE: " . self::clog($mUsage, 'c') . PHP_EOL .  PHP_EOL;
    }

    protected function beforeInit(): void
    {
        if (php_sapi_name() !== 'cli') {
            echo self::clog("Este script solo se puede ejecutar por consola.", 'r') . PHP_EOL;
            exit(1);
        }

        echo self::clog("INIT CONSOLE APP (SimpleApiRest)", 'c') . PHP_EOL;
    }

    private function showHelp(string $file): void
    {
        echo 'Usage:' . PHP_EOL;
        echo PHP_TAB . 'php ' . self::clog($file, 'y') . " " . self::clog('g|generate m|models', 'g') . PHP_EOL;
        echo PHP_TAB . 'php ' . self::clog($file, 'y') . " " . self::clog('g|generate c|controllers', 'g') . PHP_EOL;
        echo PHP_TAB . 'php ' . self::clog($file, 'y') . " " . self::clog('g|generate r|repositories', 'g') . PHP_EOL;
        echo PHP_TAB . 'php ' . self::clog($file, 'y') . " " . self::clog('serve', 'g') . " (will serve in " . self::clog('http://localhost:8080', 'c') . ")" . PHP_EOL;
        echo PHP_EOL;
    }

    public static function clog(string $str, string $type = 'w'): string
    {
        $colors = [
            'r' => 91,
            'g' => 92,
            'y' => 93,
            'b' => 94,
            'm' => 95,
            'c' => 96,
            'w' => 97,
        ];
        return "\033[" . ($colors[$type] ?? 0) . "m" . $str . "\033[0m";
    }

    private function read(string $input, mixed $default = '', bool $required = false): string
    {
        do {
            echo $input;
            $value = trim(fgets(STDIN)) ?: $default;
        } while ($required && empty($value));

        return $value;
    }

}