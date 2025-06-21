<?php

namespace MyApiRest\validators;

use MyApiRest\exceptions\RequestEntityTooLargeHttpException;

class ValidateContentSize
{

    /**
     * @throws RequestEntityTooLargeHttpException
     */
    public static function validate(int $maxSize = 10 * 1024 * 1024): void
    {
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            if ($_SERVER['CONTENT_LENGTH'] > $maxSize) {
                throw new RequestEntityTooLargeHttpException("Payload too large. Maximum allowed: $maxSize bytes.");
            }
            return;
        }

        $input = fopen('php://input', 'r');
        $size = 0;
        while (!feof($input)) {
            $size += strlen(fread($input, 8192));
            if ($size > $maxSize) {
                fclose($input);
                throw new RequestEntityTooLargeHttpException("Payload too large. Maximum allowed: $maxSize bytes");
            }
        }
        fclose($input);
    }

}