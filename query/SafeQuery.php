<?php

namespace MyApiRest\query;

use InvalidArgumentException;
use MyApiRest\core\Database;
use PDO;
use RuntimeException;

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
        $this->validateIdentifier($column);
        $param = ":w_" . count($this->params);
        $this->where[] = "`$column` = $param";
        $this->params[$param] = $value;
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