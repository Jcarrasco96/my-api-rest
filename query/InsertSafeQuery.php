<?php

namespace MyApiRest\query;

class InsertSafeQuery extends SafeQuery
{

    public function execute(): false|int|array|string
    {
        $this->validateTable();
        $this->validateData();

        $columns = array_keys($this->data);
        $params = [];
        foreach ($columns as $col) {
            $param = ":i_" . $col;
            $params[] = $param;
            $this->params[$param] = $this->data[$col];
        }
        $sql = "INSERT INTO `$this->table` (" . implode(", ", array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(", ", $params) . ")";

        $stmt = $this->pdo->prepare($sql);
        foreach ($this->params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        return $stmt->execute();
    }

}