<?php

namespace SimpleApiRest\rest;

use SimpleApiRest\exceptions\BadRequestHttpException;
use SimpleApiRest\exceptions\NotFoundHttpException;

abstract class Model
{

    protected array $attributes = [];

    protected const PROPS = [];

    public static function fromArray(array $data): static
    {
        $obj = new static();
        foreach (static::PROPS as $prop) {
            if (isset($data[$prop])) {
                $obj->$prop = $data[$prop];
            } else {
                $obj->$prop = null;
            }
        }
        return $obj;
    }

    public function toArray(): array
    {
        $data = [];

        foreach (static::PROPS as $prop) {
            $data[$prop] = $this->$prop ?? null;
        }

        if (isset($this->attributes)) {
            foreach ($this->attributes as $key => $value) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public function __get(string $name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }

        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    public function __set(string $name, $value): void
    {
        if (isset($this->$name)) {
            $this->$name = $value;
        } else {
            $this->attributes[$name] = $value;
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    public function loadRelation(string $relation_name): array|Model {
        return $this->attributes[$relation_name] ?? throw new NotFoundHttpException("The relation '$relation_name' does not exist");
    }

    public function unloadRelation(string $relation_name): void
    {
        if (isset($this->attributes[$relation_name])) {
            unset($this->attributes[$relation_name]);
        }
    }

}