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

    public function data(array $data = ['*']): self
    {
        if (in_array('*', $data, true)) {
            $this->data = ['*'];
            return $this;
        }

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

        $sql = 'SELECT ';
        $sql .= $this->data === ['*']
            ? '*'
            : implode(', ', array_map(fn($col) => "`$col`", $this->data));
        $sql .= " FROM `$this->table`";

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

    public function applyQueryParams(array $params): self
    {
        if (!empty($params['order'])) {
            $orders = explode(',', $params['order']);
            foreach ($orders as $order) {
                [$column, $direction] = array_map('trim', explode(':', $order) + [1 => 'asc']);
                $this->validateIdentifier($column);
                $dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
                $this->orderBy($column, $dir);
            }
        }

        if (isset($params['page'])) {
            if (!isset($params['limit'])) {
                $params['limit'] = 10;
            }

            $page = max(1, (int)$params['page']);
            $offset = (($params['limit'] ?? 10) * ($page - 1));
            $this->offset((int)$offset);
        }

        if (isset($params['limit'])) {
            $limit = $params['limit'];
            $this->limit(max(1, min($limit, 100)));
        }

        return $this;
    }

    public function exists(): bool
    {
        $this->validateTable();

        $sql = "SELECT 1 FROM `$this->table`";

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);

        return (bool) $stmt->fetchColumn();
    }

}