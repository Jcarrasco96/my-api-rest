<?php

namespace SimpleApiRest\query;

class UpdateSafeQuery extends SafeQuery
{

    public function execute(): int
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

        $stmt = $this->prepare($sql);

        $stmt->execute();

        return $stmt->rowCount();
    }

}