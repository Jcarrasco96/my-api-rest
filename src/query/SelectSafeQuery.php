<?php

namespace SimpleApiRest\query;

use InvalidArgumentException;
use PDO;

class SelectSafeQuery extends SafeQuery
{

    private ?int $limit = null;
    private ?int $offset = null;
    private array $orderBy = [];
    private array $groupBy = [];

    public function data(array $data): self
    {
        foreach ($data as $col) {
            $this->validateIdentifier($col);
        }
        $this->data = $data;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->validateIdentifier($column);
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException("Order by direction not valid: $direction");
        }
        $this->orderBy[] = "`$column` $direction";
        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->validateIdentifier($column);
        $this->groupBy[] = "`$column`";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    public function execute(): string|int|array|false
    {
        $this->validateTable();
        $this->validateData();

        $sql = "SELECT " . implode(', ', array_map(fn($c) => "`$c`", $this->data)) . " FROM `$this->table`";
        if ($this->where) {
            $sql .= " WHERE " . implode(" AND ", $this->where);
        }
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT :__limit";
            $this->params[':__limit'] = $this->limit;
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET :__offset";
            $this->params[':__offset'] = $this->offset;
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($this->params as $key => $val) {
            if (in_array($key, [':__offset', ':__limit'])) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}