<?php

namespace MyApiRest\core;

use PDO;
use PDOException;

class Database
{

    private static ?PDO $pdo = null;

    public static function load(): PDO
    {
        if (!self::$pdo) {
            $databaseConfig = Application::$config['db'];

            $config = $databaseConfig[$databaseConfig['driver']];

            switch ($databaseConfig['driver']) {
                case 'mysql':
                    self::loadMySqlDriver($config);
                    break;

                case 'sqlsrv':
                    self::loadSqlSrvDriver($config);
                    break;

                case 'pgsql':
                    self::loadPgSqlDriver($config);
                    break;

                case 'sqlite':
                    self::loadSqliteDriver($config);
                    break;

                default:
                    throw new PDOException("Database connection error");
            }

            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return self::$pdo;
    }

    private static function loadMySqlDriver(array $config): void
    {
        $dsn = "mysql:host={$config['host']}:{$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        self::$pdo = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_PERSISTENT => true]);
    }

    private static function loadSqlSrvDriver(array $config): void
    {
        $dsn = "sqlsrv:Server={$config['host']}:{$config['port']};Database={$config['dbname']}";
        self::$pdo = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_PERSISTENT => true]);
    }

    private static function loadPgSqlDriver(array $config): void
    {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};user={$config['user']};password={$config['password']}";
        self::$pdo = new PDO($dsn);
    }

    private static function loadSqliteDriver(array $config): void
    {
        $dsn = "sqlite:{$config['path']}";
        self::$pdo = new PDO($dsn, null, null, [PDO::ATTR_PERSISTENT => true]);
    }

}