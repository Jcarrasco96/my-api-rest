<?php

namespace SimpleApiRest\models;

use SimpleApiRest\query\SelectSafeQuery;
use SimpleApiRest\rest\Model;

abstract class UserIdentity extends Model
{

    protected static string $tableName = 'user';

    public static function can(string $userId, string $itemName): bool
    {
        return (new SelectSafeQuery())
            ->from('authentication')
            ->data()
            ->where('user_id', $userId)
            ->where('item_name', $itemName)
            ->exists();
    }

}