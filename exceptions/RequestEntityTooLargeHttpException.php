<?php

namespace MyApiRest\exceptions;

use Exception;
use Throwable;

class RequestEntityTooLargeHttpException extends Exception
{

    public function __construct(string $message = "", ?Throwable $previous = null)
    {
        parent::__construct($message, 413, $previous);
    }

}