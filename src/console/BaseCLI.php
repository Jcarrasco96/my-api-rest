<?php

namespace SimpleApiRest\console;

use PDO;
use PDOException;
use SimpleApiRest\db\Database;

abstract class BaseCLI
{

    abstract public static function generate(string $table, bool $override): void;

    protected static function camelCase(string $string): string
    {
        $string = str_replace('_', ' ', strtolower($string));
        $string = ucwords($string);
        return str_replace(' ', '', lcfirst($string));
    }

    protected static function getTableColumns(string $table): array
    {
        $pdo = Database::load();

        try {
            $columns = $pdo->query("DESCRIBE $table;")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($columns)) {
                echo PHP_TAB . "Table $table not exists!" . PHP_EOL;
                exit(1);
            }

            return $columns;
        } catch (PDOException $e) {
            echo CLI::clog($e->getMessage(), 'r') . PHP_EOL;
            exit(1);
        }
    }

    protected static function verifyInit(string $table, string $filename, bool $override, string $path): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            echo CLI::clog("Invalid name of table: $table.", 'r') . PHP_EOL;
            return;
        }

        if (!$override) {
            echo PHP_TAB . "You can use " . CLI::clog('-fc', 'c') . " for override existing class." . PHP_EOL;
        }

        $existFile = file_exists($path . "$filename.php");

        if ($existFile && !$override) {
            echo PHP_TAB . "File " . CLI::clog($filename, 'y') . " already exists!" . PHP_EOL;
            exit(1);
        }
    }

}