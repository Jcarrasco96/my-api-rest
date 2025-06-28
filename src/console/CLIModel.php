<?php

namespace SimpleApiRest\console;

use PDO;
use PDOException;
use SimpleApiRest\db\Database;

class CLIModel extends BaseCLI
{

    public static function generate(string $table, bool $override): void
    {
        echo PHP_EOL . CLI::clog("GENERATING MODELS", 'g') . PHP_EOL;

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            echo CLI::clog("Invalid name of table: $table.", 'r') . PHP_EOL;
            return;
        }

        if (!$override) {
            echo PHP_TAB . "You can use " . CLI::clog('-fc', 'c') . " for override existing class." . PHP_EOL;
        }

        $pdo = Database::load();

        try {
            $columnsQuery = $pdo->query("DESCRIBE $table;");
            $columns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo CLI::clog($e->getMessage(), 'r') . PHP_EOL;
            return;
        }

        if (empty($columns)) {
            echo PHP_TAB . "Table $table not exists!" . PHP_EOL;
            return;
        }

        $modelName = ucfirst(self::camelCase($table)) . 'Model';

        $existFile = file_exists(APP_MODELS_FOLDER . "$modelName.php");

        if ($existFile && !$override) {
            echo PHP_TAB . "Model " . CLI::clog($modelName, 'y') . " already exists!" . PHP_EOL;
            return;
        }

        $modelNamespace = CLI::$config['modelNamespace'];

        $modelContent = "<?php" . PHP_EOL . PHP_EOL;
        $modelContent .= "namespace $modelNamespace;" . PHP_EOL . PHP_EOL;
        $modelContent .= "use Ramsey\\Uuid\\Uuid;" . PHP_EOL;
        $modelContent .= "use SimpleApiRest\\exceptions\\NotFoundHttpException;" . PHP_EOL;
        $modelContent .= "use SimpleApiRest\\query\\InsertSafeQuery;" . PHP_EOL;
        $modelContent .= "use SimpleApiRest\\query\\SelectSafeQuery;" . PHP_EOL;
        $modelContent .= "use SimpleApiRest\\query\\UpdateSafeQuery;" . PHP_EOL;
        $modelContent .= "use SimpleApiRest\\rest\\Model;" . PHP_EOL . PHP_EOL;
        $modelContent .= "class $modelName extends Model" . PHP_EOL;
        $modelContent .= "{" . PHP_EOL . PHP_EOL;
        $modelContent .= PHP_TAB . "protected static string \$tableName = '$table';" . PHP_EOL . PHP_EOL;

        foreach ($columns as $column) {
            $dataType = self::dataType($column['Type']);
            $modelContent .= PHP_TAB . "public $dataType \${$column['Field']};" . PHP_EOL;
        }

        $modelContent .= PHP_EOL;
        $modelContent .= self::__getFunction();
        $modelContent .= self::fromArrayFunction($columns);
        $modelContent .= self::extendsFunction();
        $modelContent .= "}" . PHP_EOL;

        file_put_contents(APP_MODELS_FOLDER . "$modelName.php", $modelContent);
        echo PHP_TAB . "Model " . CLI::clog($modelName, 'c') . " generated successfully!" . PHP_EOL;
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

    private static function fromArrayFunction(array $columns): string
    {
        $fields = array_column($columns, 'Field');
        $props = implode("', '", $fields);

        $content = <<< PHP
            protected static function fromArray(array \$data): self
            {
                \$props = [
                    '$props'
                ];
                \$obj = new self();
                foreach (\$props as \$prop) {
                    if (isset(\$data[\$prop])) {
                        \$obj->\$prop = \$data[\$prop];
                    }
                }
                return \$obj;
            }
        PHP;

        return $content . PHP_EOL . PHP_EOL;
    }

    private static function __getFunction(): string
    {
        $content = <<< PHP
            public function __get(string \$name)
            {
                if (isset(\$this->\$name)) {
                    return \$this->\$name;
                }
                
                if (isset(\$this->attributes[\$name])) {
                    return \$this->attributes[\$name];
                }
                
                return null;
            }
        PHP;

        return $content . PHP_EOL . PHP_EOL;
    }

    private static function extendsFunction(): string
    {
        $content = <<< PHP
            /**
             * @throws NotFoundHttpException
             */
            public static function create(array \$data): false|self
            {
                \$uuid = Uuid::uuid4()->toString();

                \$data['id'] = \$uuid;
        
                \$inserted = (new InsertSafeQuery())
                    ->from(self::\$tableName)
                    ->data(\$data)
                    ->execute();
        
                if (\$inserted) {
                    return self::findById(\$uuid);
                }
        
                return false;
            }
            
            /**
             * @throws NotFoundHttpException
             */
            public static function update(string \$uuid, array \$data): false|self
            {
                unset(\$data['id']);

                \$updated = (new UpdateSafeQuery())
                    ->from(self::\$tableName)
                    ->data(\$data)
                    ->where('id', \$uuid)
                    ->execute();
        
                if (\$updated) {
                    return self::findById(\$uuid);
                }
        
                return false;
            }
            
            /**
             * @throws NotFoundHttpException
             */
            public static function findById(string \$uuid): self
            {
                \$data = (new SelectSafeQuery())
                    ->from(self::\$tableName)
                    ->data()
                    ->where('id', \$uuid)
                    ->limit(1)
                    ->execute();
        
                if (empty(\$data)) {
                    throw new NotFoundHttpException("Client with id \$uuid not found");
                }
        
                return self::fromArray(array_shift(\$data));
            }
            
            public static function findAll(): array
            {
                \$data = (new SelectSafeQuery())
                    ->from(self::\$tableName)
                    ->data()
                    ->applyQueryParams(\$_GET)
                    ->execute();
                
                return array_map(static fn(array \$data) => self::fromArray(\$data), \$data);
            }
        PHP;

        return $content . PHP_EOL . PHP_EOL;
    }

}