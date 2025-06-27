<?php

namespace SimpleApiRest\exceptions;

use Exception;
use Throwable;

class NotFoundHttpException extends Exception
{

    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }

}