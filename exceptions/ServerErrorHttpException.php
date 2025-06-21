<?php

namespace MyApiRest\exceptions;

use Exception;
use Throwable;

class ServerErrorHttpException extends Exception
{

    public function __construct(string $message = "", ?Throwable $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }

}