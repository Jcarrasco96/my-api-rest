<?php

namespace SimpleApiRest\rest;

use SimpleApiRest\query\DeleteSafeQuery;

abstract class Model
{

    protected static string $tableName;

    protected array $attributes = [];

    abstract public static function findById(string $uuid): self;

    abstract public static function create(array $data): false|self;

    abstract public static function update(string $uuid, array $data): false|self;

    abstract public static function findAll(): array;

    public static function delete(string $uuid): bool
    {
        return (new DeleteSafeQuery())
            ->from(static::$tableName)
            ->where('id', $uuid)
            ->execute();
    }

    public function __set(string $name, $value): void
    {
        if (isset($this->$name)) {
            $this->$name = $value;
        } else {
            $this->attributes[$name] = $value;
        }
    }

}