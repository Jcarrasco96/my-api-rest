<?php

namespace SimpleApiRest\console;

use PDO;
use SimpleApiRest\db\Database;

class CLIRepository extends BaseCLI
{

    public static function generate(bool $override): void
    {
        echo PHP_EOL . CLI::clog("GENERATING REPOSITORIES", 'g') . PHP_EOL;

        if (!$override) {
            echo PHP_TAB . "You can use " . CLI::clog('-fc', 'c') . " for override existing class." . PHP_EOL;
        }

        $pdo = Database::load();

        $query = $pdo->query("SHOW TABLES;");
        $tables = $query->fetchAll(PDO::FETCH_COLUMN);

        $repositoryNamespace = CLI::$config['repositoryNamespace'];

        foreach ($tables as $table) {
            $repositoryName = ucfirst(self::camelCase($table)) . 'Repository';

            $existFile = file_exists(APP_REPOSITORY_FOLDER . "$repositoryName.php");

            if ($existFile && !$override) {
                echo PHP_TAB . "Repository " . CLI::clog($repositoryName, 'y') . " already exists!" . PHP_EOL;
                continue;
            }

            $columnsQuery = $pdo->query("DESCRIBE $table;");
            $columns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);
            $filteredColumns = array_filter($columns, fn($col) => $col['Field'] !== 'id');

            $repositoryContent = "<?php" . PHP_EOL . PHP_EOL;
            $repositoryContent .= "namespace $repositoryNamespace;" . PHP_EOL . PHP_EOL;
            $repositoryContent .= "use SimpleApiRest\\core\\Database;" . PHP_EOL;
            $repositoryContent .= "use PDO;" . PHP_EOL;
            $repositoryContent .= "use Ramsey\Uuid\Uuid;" . PHP_EOL;
            $repositoryContent .= PHP_EOL;
            $repositoryContent .= "class $repositoryName {" . PHP_EOL . PHP_EOL;
            $repositoryContent .= PHP_TAB . "private PDO \$pdo;" . PHP_EOL . PHP_EOL;
            $repositoryContent .= PHP_TAB . "public function __construct() {" . PHP_EOL;
            $repositoryContent .= PHP_TAB . PHP_TAB . "\$this->pdo = Database::load();" . PHP_EOL;
            $repositoryContent .= PHP_TAB . "}" . PHP_EOL . PHP_EOL;
            $repositoryContent .= self::generateCreateMethod($table, $filteredColumns);
//            $repositoryContent .= self::generateRelationshipMethods($pdo, $table);
            $repositoryContent .= self::generateFindByIdMethod($table);
            $repositoryContent .= self::generateFindAllMethod($table);
            $repositoryContent .= self::generateUpdateMethod($table, $filteredColumns);
            $repositoryContent .= self::generateDeleteMethod($table);
            $repositoryContent .= "}" . PHP_EOL;

            file_put_contents(APP_REPOSITORY_FOLDER . "$repositoryName.php", $repositoryContent);
            echo PHP_TAB . "Repository " . CLI::clog($repositoryName, 'c') . " generated successfully!" . PHP_EOL;
        }
    }

    private static function generateRelationshipMethods(PDO $pdo, string $table): string
    {
        $relationships = self::detectRelationships($pdo, $table);
        $methods = "";

        foreach ($relationships as $relation) {
            if ($relation['master_table'] === $table) {
                $methods .= self::generateMasterDetailMethods($pdo, $table, $relation['detail_table'], $relation);
            }
        }

        return $methods;
    }

    private static function detectRelationships(PDO $pdo, string $table): array
    {
        $query = "
            SELECT 
                TABLE_NAME AS detail_table, 
                COLUMN_NAME AS detail_column, 
                REFERENCED_TABLE_NAME AS master_table, 
                REFERENCED_COLUMN_NAME AS master_column
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE 
                TABLE_SCHEMA = DATABASE() 
                AND (TABLE_NAME = :table OR REFERENCED_TABLE_NAME = :table)
                AND REFERENCED_TABLE_NAME IS NOT NULL;
            ;
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':table', $table);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function generateMasterDetailMethods(PDO $pdo, string $table, mixed $detail_table, mixed $relation): string
    {
        $masterColumns = self::getTableColumns($pdo, $table);
        $detailColumns = self::getTableColumns($pdo, $detail_table);
        $detailColumns = array_filter($detailColumns, fn($col) => !in_array($col['Field'], ['id', 'status', 'criado_em', 'atualizado_em', 'deletado_em', 'deletado', 'aprovado_por']));
        $masterColumns = array_filter($masterColumns, fn($col) => !in_array($col['Field'], ['id', 'status', 'criado_em', 'atualizado_em', 'deletado_em', 'deletado', 'aprovado_por']));
        $masterFields = array_filter($masterColumns, fn($col) => $col['Field'] !== 'id');
        $detailFields = array_filter($detailColumns, fn($col) => $col['Field'] !== 'id');

        $masterSetClause = implode(', ', array_map(fn($col) => "{$col['Field']} = :{$col['Field']}", $masterFields));
        $detailInsertColumns = implode(', ', array_column($detailFields, 'Field'));
        $detailInsertPlaceholders = implode(', ', array_map(fn($col) => ":{$col['Field']}", $detailFields));

        $methods = "    public function getMasterWithDetails() {\n";
        $methods .= "        try {\n";
        $methods .= "            \$masterQuery = \"SELECT * FROM $table WHERE deletado = 0\";\n";
        $methods .= "            \$stmtMaster = \$this->pdo->query(\$masterQuery);\n";
        $methods .= "            \$masterRecords = \$stmtMaster->fetchAll(PDO::FETCH_ASSOC);\n";
        $methods .= "\n";
        $methods .= "            foreach (\$masterRecords as &\$record) {\n";
        $methods .= "                \$detailQuery = \"SELECT * FROM $detail_table WHERE {$relation['detail_column']} = :master_id AND deletado = 0\";\n";
        $methods .= "                \$stmtDetail = \$this->pdo->prepare(\$detailQuery);\n";
        $methods .= "                \$stmtDetail->bindValue(':master_id', \$record['id'], PDO::PARAM_INT);\n";
        $methods .= "                \$stmtDetail->execute();\n";
        $methods .= "                \$record['details'] = \$stmtDetail->fetchAll(PDO::FETCH_ASSOC);\n";
        $methods .= "            }\n";
        $methods .= "\n";
        $methods .= "            return \$masterRecords;\n";
        $methods .= "        } catch (PDOException \$e) {\n";
        $methods .= "            return \$this->generateErrorResponse(\$e);\n";
        $methods .= "        }\n";
        $methods .= "    }\n\n";

        $methods .= "    public function saveMasterDetail(\$data) {\n";
        $methods .= "        \$this->pdo->beginTransaction();\n";
        $methods .= "        try {\n";

        $methods .= "            \$masterData = \$data;\n";
        $methods .= "            \$detailsData = \$masterData->details ?? [];\n";
        $methods .= "            unset(\$masterData->details);\n";

        $methods .= "            if (!empty(\$masterData->id)) {\n";
        $methods .= "                \$queryMaster = \"UPDATE $table SET $masterSetClause WHERE id = :id\";\n";
        $methods .= "                \$stmtMaster = \$this->pdo->prepare(\$queryMaster);\n";
        $methods .= "                \$stmtMaster->bindValue(':id', \$masterData->id, PDO::PARAM_INT);\n";
        $methods .= "            } else {\n";
        $methods .= "                \$queryMaster = \"INSERT INTO $table (" . implode(', ', array_column($masterFields, 'Field')) . ") VALUES (" . implode(', ', array_map(fn($col) => ":{$col['Field']}", $masterFields)) . ")\";\n";
        $methods .= "                \$stmtMaster = \$this->pdo->prepare(\$queryMaster);\n";
        $methods .= "            }\n";

        foreach ($masterFields as $field) {
            $methods .= "            \$stmtMaster->bindValue(':{$field['Field']}', \$masterData->{$field['Field']} ?? null);\n";
        }
        $methods .= "            \$stmtMaster->execute();\n";
        $methods .= "            \$masterId = \$masterData->id ?? \$this->pdo->lastInsertId();\n\n";

        $methods .= "            \$queryInsertDetails = \"INSERT INTO $detail_table ($detailInsertColumns) VALUES ($detailInsertPlaceholders)\";\n";
        $methods .= "            \$stmtInsertDetails = \$this->pdo->prepare(\$queryInsertDetails);\n";
        $methods .= "            foreach (\$detailsData as \$detail) {\n";
        $methods .= "                \$detail->curso_id = \$masterId;\n";
        foreach ($detailFields as $field) {
            $methods .= "                \$stmtInsertDetails->bindValue(':{$field['Field']}', \$detail->{$field['Field']} ?? null);\n";
        }
        $methods .= "                \$stmtInsertDetails->execute();\n";
        $methods .= "            }\n\n";

        $methods .= "            \$this->pdo->commit();\n";
        $methods .= "            return true;\n";
        $methods .= "        } catch (PDOException \$e) {\n";
        $methods .= "            \$this->pdo->rollBack();\n";
        $methods .= "            return \$this->generateErrorResponse(\$e);\n";
        $methods .= "        }\n";
        $methods .= "    }\n\n";

        $methods .= "    public function excluiDetail(\$detailId) {\n";
        $methods .= "        \$query = \"UPDATE $detail_table SET deletado = 1, status = 'pendente_exclusao' WHERE id = :id\";\n";
        $methods .= "        try {\n";
        $methods .= "            \$stmt = \$this->pdo->prepare(\$query);\n";
        $methods .= "            \$stmt->bindValue(':id', \$detailId, PDO::PARAM_INT);\n";
        $methods .= "            return \$stmt->execute();\n";
        $methods .= "        } catch (PDOException \$e) {\n";
        $methods .= "            return \$this->generateErrorResponse(\$e);\n";
        $methods .= "        }\n";
        $methods .= "    }\n\n";

        $methods .= "    public function atualizaDetail(\$detailId, \$data) {\n";
        $methods .= "        \$setClause = implode(', ', array_map(fn(\$key) => \"\$key = :\$key\", array_keys((array) \$data)));\n";
        $methods .= "        \$query = \"UPDATE $detail_table SET \$setClause WHERE id = :id\";\n";
        $methods .= "        try {\n";
        $methods .= "            \$stmt = \$this->pdo->prepare(\$query);\n";
        $methods .= "            foreach (\$data as \$key => \$value) {\n";
        $methods .= "                \$stmt->bindValue(\":\$key\", \$value);\n";
        $methods .= "            }\n";
        $methods .= "            \$stmt->bindValue(':id', \$detailId, PDO::PARAM_INT);\n";
        $methods .= "            return \$stmt->execute();\n";
        $methods .= "        } catch (PDOException \$e) {\n";
        $methods .= "            return \$this->generateErrorResponse(\$e);\n";
        $methods .= "        }\n";
        $methods .= "    }\n\n";

        return $methods;
    }

    private static function getTableColumns(PDO $pdo, string $table): array
    {
        return $pdo->query("DESCRIBE $table;")->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function generateFindByIdMethod(mixed $table): string
    {
        $method = PHP_TAB . "public function findById(string \$id) {" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$query = \"SELECT * FROM $table WHERE id = :id;\";" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt = \$this->pdo->prepare(\$query);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt->bindValue(':id', \$id);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt->execute();" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "return \$stmt->fetch(PDO::FETCH_ASSOC) ?: null;" . PHP_EOL;
        $method .= PHP_TAB . "}" . PHP_EOL . PHP_EOL;

        return $method;
    }

    private static function generateCreateMethod(mixed $table, array $columns): string
    {
        $fields = array_column($columns, 'Field');
        $placeholders = array_map(fn($col) => ":$col", $fields);

        $method = PHP_TAB . "public function create(mixed \$data): bool {" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$uuid = Uuid::uuid4()->toString();" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$fields = ['" . implode("', '", $fields) . "'];" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$query = \"INSERT INTO $table (id, " . implode(', ', $fields) . ") VALUES (:id, " . implode(', ', $placeholders) . ")\";" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt = \$this->pdo->prepare(\$query);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt->bindValue(':id', \$uuid);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "foreach (\$fields as \$field) {" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . PHP_TAB . "\$stmt->bindValue(\":\$field\", \$data->\$field);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "}" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "return \$stmt->execute();" . PHP_EOL;
        $method .= PHP_TAB . "}" . PHP_EOL . PHP_EOL;

        return $method;
    }

    private static function generateFindAllMethod(mixed $table): string
    {
        $method = PHP_TAB . "public function findAll(): array {" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$query = \"SELECT * FROM $table;\";" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt = \$this->pdo->query(\$query);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "return \$stmt->fetchAll(PDO::FETCH_ASSOC);" . PHP_EOL;
        $method .= PHP_TAB . "}" . PHP_EOL .  PHP_EOL;

        return $method;
    }

    private static function generateUpdateMethod(mixed $table, array $columns): string
    {
        $fields = array_column($columns, 'Field');
        $setClause = implode(', ', array_map(fn($col) => "$col = :$col", $fields));

        $method = PHP_TAB . "public function update(string \$id, mixed \$data): bool {" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$fields = ['" . implode("', '", $fields) . "'];" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$query = \"UPDATE $table SET $setClause WHERE id = :id;\";" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt = \$this->pdo->prepare(\$query);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt->bindValue(':id', \$id);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "foreach (\$fields as \$field) {" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . PHP_TAB . "\$stmt->bindValue(\":\$field\", \$data->\$field);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "}" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "return \$stmt->execute();" . PHP_EOL;
        $method .= PHP_TAB . "}" . PHP_EOL . PHP_EOL;

        return $method;
    }

    private static function generateDeleteMethod(mixed $table): string
    {
        $method = PHP_TAB . "public function delete(string \$id): bool {" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$query = \"DELETE FROM $table WHERE id = :id;\";" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt = \$this->pdo->prepare(\$query);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "\$stmt->bindValue(':id', \$id);" . PHP_EOL;
        $method .= PHP_TAB . PHP_TAB . "return \$stmt->execute();" . PHP_EOL;
        $method .= PHP_TAB . "}" . PHP_EOL . PHP_EOL;

        return $method;
    }

}