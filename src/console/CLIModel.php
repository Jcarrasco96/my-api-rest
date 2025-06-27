<?php

namespace SimpleApiRest\console;

use PDO;
use SimpleApiRest\db\Database;

class CLIModel extends BaseCLI
{

    public static function generate(bool $override): void
    {
        echo PHP_EOL . CLI::clog("GENERATING MODELS", 'g') . PHP_EOL;

        if (!$override) {
            echo PHP_TAB . "You can use " . CLI::clog('-fc', 'c') . " for override existing class." . PHP_EOL;
        }

        $pdo = Database::load();

        $query = $pdo->query("SHOW TABLES;");
        $tables = $query->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $columnsQuery = $pdo->query("DESCRIBE $table;");
            $columns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);

            $modelName = ucfirst(self::camelCase($table));

            $existFile = file_exists(APP_MODELS_FOLDER . "$modelName.php");

            if ($existFile && !$override) {
                echo PHP_TAB . "Model " . CLI::clog($modelName, 'y') . " already exists!" . PHP_EOL;
                continue;
            }

            $modelNamespace = CLI::$config['modelNamespace'];

            $modelContent = "<?php" . PHP_EOL . PHP_EOL;
            $modelContent .= "namespace $modelNamespace;" . PHP_EOL . PHP_EOL;
            $modelContent .= "class $modelName {" . PHP_EOL . PHP_EOL;

            foreach ($columns as $column) {
                $dataType = self::dataType($column['Type']);
                $modelContent .= PHP_TAB . "public $dataType \${$column['Field']};" .  PHP_EOL . PHP_EOL;
            }

//            $modelContent .= "\n    public function __construct(\$data) {\n";
//            foreach ($columns as $column) {
//                $field = $column['Field'];
//                $dataType = $this->dataType($column['Type']);
//
//                if ($dataType == 'int') {
//                    $modelContent .= "        \$this->$field = \$data->$field;\n";
//                    continue;
//                }
//
//                $modelContent .= "        \$this->$field = \$data->$field ?? null;\n";
//            }
//            $modelContent .= "    }\n";

            $modelContent .= "}" . PHP_EOL;

            file_put_contents(APP_MODELS_FOLDER . "$modelName.php", $modelContent);
            echo PHP_TAB . "Model " . CLI::clog($modelName, 'c') . " generated successfully!" . PHP_EOL;
        }
    }

    private static function dataType(string $type): string
    {
        $dataType = 'bool';

        if (str_starts_with($type, 'int')) {
            $dataType = 'int';
        }
        if (str_starts_with($type, 'char') || str_starts_with($type, 'timestamp')) {
            $dataType = 'string';
        }
        if (str_starts_with($type, 'date') || str_starts_with($type, 'datetime')) {
            $dataType = 'string';
        }
        if (str_starts_with($type, 'enum')) {
            $dataType = 'string';
        }

        return $dataType;
    }

}