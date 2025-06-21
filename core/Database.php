<?php

namespace MyApiRest\core;

use PDO;
use PDOException;

class Database
{

    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (!self::$connection) {
            $databaseConfig = Application::$config['db'];

            $config = $databaseConfig[$databaseConfig['driver']];

            switch ($databaseConfig['driver']) {
                case 'mysql':
                    $dsn = "mysql:host={$config['host']}:{$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
                    self::$connection = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_PERSISTENT => true]);
                    break;

                case 'sqlsrv':
                    $dsn = "sqlsrv:Server={$config['host']}:{$config['port']};Database={$config['dbname']}";
                    self::$connection = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_PERSISTENT => true]);
                    break;

                case 'pgsql':
                    $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};user={$config['user']};password={$config['password']}";
                    self::$connection = new PDO($dsn);
                    break;

                case 'sqlite':
                    $dsn = "sqlite:{$config['path']}";
                    self::$connection = new PDO($dsn, null, null, [PDO::ATTR_PERSISTENT => true]);
                    break;

                default:
                    throw new PDOException("Database connection error");
            }

            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return self::$connection;
    }

    public static function findById(string $tableName, string $id): array
    {
        $stmt = self::getConnection()->prepare("SELECT * FROM `$tableName` WHERE id = :id;");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findAll(string $tableName): array
    {
        $stmt = self::getConnection()->query("SELECT * FROM `$tableName`;");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function delete(string $tableName, int $id): bool
    {
        $stmt = self::getConnection()->prepare("DELETE FROM `$tableName` WHERE id = :id;");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function tableColumns(string $tableName): array
    {
        $stmt = self::getConnection()->prepare("DESCRIBE `$tableName`;");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}