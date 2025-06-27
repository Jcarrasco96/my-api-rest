<?php

namespace SimpleApiRest\console;

use PDO;
use SimpleApiRest\core\BaseApplication;
use SimpleApiRest\db\Database;
use SimpleApiRest\exceptions\ServerErrorHttpException;

class AuthenticationMigration
{

    private string $tableName = 'authentication';

    /**
     * @throws ServerErrorHttpException
     */
    public function migrate(): void
    {
        $pdo = Database::load();

        if ($this->tableExists($pdo)) {
            throw new ServerErrorHttpException("Database table '$this->tableName' already exists.");
        }

        $sqlCreate = "CREATE TABLE IF NOT EXISTS `$this->tableName` (
            `id` char(36) NOT NULL,
            `item_name` char(64) NOT NULL,
            `user_id` char(36) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp()
        );";

        $sqlAlterTable = "ALTER TABLE `$this->tableName`
            ADD PRIMARY KEY (`id`),
            ADD KEY `user_id` (`user_id`);";

        $sqlConstraint = "ALTER TABLE `$this->tableName`
            ADD CONSTRAINT `{$this->tableName}_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;";

        $pdo->prepare($sqlCreate)->execute();
        $pdo->prepare($sqlAlterTable)->execute();
        $pdo->prepare($sqlConstraint)->execute();
    }

    /**
     * @throws ServerErrorHttpException
     */
    private function tableExists(PDO $pdo): bool {
        $databaseConfig = BaseApplication::$config['db'];
        $config = $databaseConfig[$databaseConfig['driver']];

        if (!isset($config['dbname'])) {
            throw new ServerErrorHttpException("Database configuration is missing.");
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table LIMIT 1;");
        $stmt->execute(['db' => $config['dbname'], 'table' => $this->tableName]);
        return (bool) $stmt->fetchColumn();
    }

}