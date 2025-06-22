<?php

namespace SimpleApiRest\core;

use SimpleApiRest\query\DeleteSafeQuery;

abstract class Model
{

    protected static string $tableName;

    abstract public static function findById(string $uuid): array;

    abstract public static function create(array $data): bool|array;

    abstract public static function update(string $uuid, array $data): bool|array;

    abstract public static function findAll(): array;

    public static function delete(string $uuid): bool
    {
        return (new DeleteSafeQuery())
            ->from(static::$tableName)
            ->where('id', $uuid)
            ->execute();
    }

}