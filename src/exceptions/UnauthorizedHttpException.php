<?php

namespace SimpleApiRest\exceptions;

use Exception;
use Throwable;

class UnauthorizedHttpException extends Exception
{

    public function __construct(string $message = "", ?Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }

}