<?php

namespace SimpleApiRest\cron;

use Throwable;

abstract class Task
{

    public string $name;

    public function __construct()
    {
        $this->name = static::class;
    }

    abstract public function run(): void;

    public function handleError(Throwable $e): void
    {
        echo "[ERROR] $this->name: " . $e->getMessage() . "\n";
    }

}