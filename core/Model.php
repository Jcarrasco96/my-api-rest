<?php

namespace MyApiRest\core;

abstract class Model
{

    protected static string $tableName;

    public static function findById(string $uuid): array
    {
        return Database::findById(static::$tableName, $uuid);
    }

    abstract public static function create(array $data): bool|array;

    abstract public static function update(string $uuid, array $data): bool|array;

    public static function findAll(): array
    {
        return Database::findAll(static::$tableName);
    }

    public static function delete($uuid): bool
    {
        return Database::delete(static::$tableName, $uuid);
    }

    public static function tableColumns(): array
    {
        return Database::tableColumns(static::$tableName);
    }

}