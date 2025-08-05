<?php

namespace SimpleApiRest\query;

class DeleteSafeQuery extends SafeQuery
{

    public function execute(): int
    {
        $this->validateTable();
        $this->validateWhere();

        $sql = "DELETE FROM `$this->table` WHERE " . implode(" AND ", $this->where);

        $stmt = $this->prepare($sql);

        $stmt->execute();

        return $stmt->rowCount();
    }

}