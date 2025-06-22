<?php

namespace SimpleApiRest\validators;

use SimpleApiRest\exceptions\BadRequestHttpException;
use SimpleApiRest\exceptions\UnsupportedMediaTypeHttpException;

class ValidateContentType
{

    /**
     * @throws BadRequestHttpException
     * @throws UnsupportedMediaTypeHttpException
     */
    public static function validate(array $allowedTypes = []): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (!in_array(explode(';', $contentType)[0], $allowedTypes)) {
            throw new UnsupportedMediaTypeHttpException('Unsupported Content-Type. Expected: ' . implode(', ', $allowedTypes));
        }

        if (str_contains($contentType, 'application/json')) {
            json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BadRequestHttpException('Invalid JSON payload.');
            }
        }
    }

}