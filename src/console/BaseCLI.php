<?php

namespace SimpleApiRest\console;

abstract class BaseCLI
{

    abstract public static function generate(string $table, bool $override): void;

    protected static function camelCase(string $string): string
    {
        $string = str_replace('_', ' ', strtolower($string));
        $string = ucwords($string);
        return str_replace(' ', '', lcfirst($string));
    }

}