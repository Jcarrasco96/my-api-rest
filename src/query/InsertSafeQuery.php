<?php

namespace SimpleApiRest\query;

class InsertSafeQuery extends SafeQuery
{

    public function execute(): bool
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

        $stmt = $this->prepare($sql);

        return $stmt->execute();
    }

}