<?php

namespace MAR\core;

abstract class BaseModel
{

    protected static string $tableName;

    public static function findById(string $uuid): array
    {
        return MyApiRestApp::$database->findById("SELECT * FROM `" . static::$tableName . "` WHERE id = :id", $uuid);
    }

    abstract public static function create(array $data): bool|array;

    abstract public static function update(string $uuid, array $data): bool|array;

    public static function findAll(): array
    {
        return MyApiRestApp::$database->findAll("SELECT * FROM `" . static::$tableName . "`");
    }

    public static function delete($uuid): bool
    {
        return MyApiRestApp::$database->delete("DELETE FROM `" . static::$tableName. "` WHERE id = :id", $uuid);
    }

}