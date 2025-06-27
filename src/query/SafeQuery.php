<?php

namespace SimpleApiRest\query;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use SimpleApiRest\db\Database;

abstract class SafeQuery
{

    protected PDO $pdo;
    protected string $table = '';
    protected array $where = [];
    protected array $params = [];
    protected array $data = [];

    public function __construct()
    {
        $this->pdo = Database::load();
    }

    public function data(array $data): self
    {
        foreach ($data as $col => $val) {
            $this->validateIdentifier($col);
            $this->data[$col] = $val;
        }
        return $this;
    }

    public function from(string $table): self
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException("Invalid name of table: $table");
        }
        $this->table = $table;
        return $this;
    }

    public function where(string $column, mixed $value): self
    {
        return $this->whereAdvanced($column, '=', $value);
    }

    public function whereAdvanced(string $column, string $operator, mixed $value): self
    {
        $this->validateIdentifier($column);

        $allowed = ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE'];
        if (!in_array(strtoupper($operator), $allowed)) {
            throw new InvalidArgumentException("Operator not allowed: $operator");
        }

        $param = ':w_' . count($this->params);
        $this->where[] = "`$column` $operator $param";
        $this->params[$param] = $value;
        return $this;
    }

    public function whereGroup(callable $callback, string $join = 'OR'): self
    {
        $join = strtoupper($join) === 'AND' ? 'AND' : 'OR';
        $conditions = [];
        $localParams = [];

        $add = function (string $column, string $operator, mixed $value) use (&$conditions, &$localParams) {
            $this->validateIdentifier($column);
            $operator = strtoupper(trim($operator));

            $paramBase = ':g_' . count($this->params) . '_' . count($localParams);

            switch ($operator) {
                case 'IN':
                case 'NOT IN':
                    if (!is_array($value) || empty($value)) {
                        throw new InvalidArgumentException("The value for $operator must be a non-empty array.");
                    }
                    $placeholders = [];
                    foreach ($value as $i => $val) {
                        $param = $paramBase . '_' . $i;
                        $placeholders[] = $param;
                        $localParams[$param] = $val;
                    }
                    $conditions[] = "`$column` $operator (" . implode(', ', $placeholders) . ")";
                    break;

                case 'BETWEEN':
                case 'NOT BETWEEN':
                    if (!is_array($value) || count($value) !== 2) {
                        throw new InvalidArgumentException("The value for $operator must be an array with 2 elements.");
                    }
                    $param1 = $paramBase . '_from';
                    $param2 = $paramBase . '_to';
                    $conditions[] = "`$column` $operator $param1 AND $param2";
                    $localParams[$param1] = $value[0];
                    $localParams[$param2] = $value[1];
                    break;

                default:
                    $allowed = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE'];
                    if (!in_array($operator, $allowed, true)) {
                        throw new InvalidArgumentException("Operator not allowed: $operator");
                    }
                    $param = $paramBase;
                    $conditions[] = "`$column` $operator $param";
                    $localParams[$param] = $value;
            }
        };

        $callback($add);

        if (!empty($conditions)) {
            $this->where[] = '(' . implode(" $join ", $conditions) . ')';
            $this->params += $localParams;
        }

        return $this;
    }

    abstract public function execute(): string|int|array|false;

    protected function validateIdentifier(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new InvalidArgumentException("Invalid name: $name");
        }
    }

    protected function validateTable(): void
    {
        if (empty($this->table)) {
            throw new RuntimeException("TABLE name cannot be empty.");
        }
    }

    protected function validateWhere(): void
    {
        if (empty($this->where)) {
            throw new RuntimeException("WHERE cannot be empty.");
        }
    }

    protected function validateData(): void
    {
        if (empty($this->data)) {
            throw new RuntimeException("DATA or SELECT cannot be empty.");
        }
    }

}