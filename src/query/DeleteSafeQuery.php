<?php

namespace SimpleApiRest\query;

class DeleteSafeQuery extends SafeQuery
{

    public function execute(): string|int|array|false
    {
        $this->validateTable();
        $this->validateWhere();

        $sql = "DELETE FROM `$this->table` WHERE " . implode(" AND ", $this->where);
        $stmt = $this->pdo->prepare($sql);

        foreach ($this->params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();
        return $stmt->rowCount();
    }

}