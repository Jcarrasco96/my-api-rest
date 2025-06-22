<?php

namespace SimpleApiRest\exceptions;

use Exception;
use Throwable;

class BadRequestHttpException extends Exception
{
    public function __construct(string $message = "", ?Throwable $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }

}