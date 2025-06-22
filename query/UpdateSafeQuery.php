<?php

namespace MyApiRest\query;

class UpdateSafeQuery extends SafeQuery
{

    public function execute(): string|int|array|false
    {
        $this->validateTable();
        $this->validateWhere();
        $this->validateData();

        $sets = [];
        foreach ($this->data as $col => $val) {
            $param = ":u_" . $col;
            $sets[] = "`$col` = $param";
            $this->params[$param] = $val;
        }
        $sql = "UPDATE `$this->table` SET " . implode(", ", $sets) . " WHERE " . implode(" AND ", $this->where);

        $stmt = $this->pdo->prepare($sql);
        foreach ($this->params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();

        return $stmt->rowCount();
    }

}