<?php

namespace MAR\core;

use PDO;
use PDOException;

class Database
{

    public ?PDO $connection;

    public function __construct()
    {
        $databaseConfig = MyApiRestApp::$config['db'];

        $config = $databaseConfig[$databaseConfig['driver']];

        switch ($databaseConfig['driver']) {
            case 'mysql':
                $dsn = "mysql:host={$config['host']}:{$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
                $this->connection = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_PERSISTENT => true]);
                break;

            case 'sqlsrv':
                $dsn = "sqlsrv:Server={$config['host']}:{$config['port']};Database={$config['dbname']}";
                $this->connection = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_PERSISTENT => true]);
                break;

            case 'pgsql':
                $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};user={$config['user']};password={$config['password']}";
                $this->connection = new PDO($dsn);
                break;

            case 'sqlite':
                $dsn = "sqlite:{$config['path']}";
                $this->connection = new PDO($dsn, null, null, [PDO::ATTR_PERSISTENT => true]);
                break;

            default:
                throw new PDOException("Database connection error");
        }

        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function __destruct()
    {
        if ($this->connection) {
            $this->connection = null;
        }
    }

    public function findById(string $query, string $id): array
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException) {
            return [];
        }
    }

    public function findAll(string $query): array
    {
        try {
            $stmt = $this->connection->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }

    public function delete(string $query, int $id): bool
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException) {
            return false;
        }
    }

}