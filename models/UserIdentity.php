<?php

namespace MAR\models;

use MAR\core\BaseModel;

abstract class UserIdentity extends BaseModel
{

    protected static string $tableName = 'user';

    abstract public static function can(string $userId, string $itemName): bool;

}