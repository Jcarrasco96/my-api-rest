<?php

namespace SimpleApiRest\rest;

abstract class Repository
{

    abstract protected static function tableName(): string;

    abstract public static function findAll(): array;

    abstract public static function findById(string $uuid, bool $throwsOnError = false): Model;

    abstract public static function create(array $data): Model;

    abstract public static function update(string $uuid, array $data): Model;

    abstract public static function delete(string $uuid): bool;

}