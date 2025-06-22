<?php

namespace SimpleApiRest\models;

use SimpleApiRest\core\Model;

abstract class UserIdentity extends Model
{

    protected static string $tableName = 'user';

    abstract public static function can(string $userId, string $itemName): bool;

}