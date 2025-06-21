<?php

namespace MyApiRest\core;

use MyApiRest\exceptions\ServerErrorHttpException;

class JsonResponse
{

    /**
     * @throws ServerErrorHttpException
     */
    public static function response(string $message = '', array $data = []): string
    {
        $status = 'success';
        $statusCode = 200;

        if (isset($data["statusCode"])) {
            http_response_code($data["statusCode"]);

            if ($data['statusCode'] < 200 || $data['statusCode'] > 299) {
                $status = 'error';
                $statusCode = $data['statusCode'];
            }

            unset($data["statusCode"]);
        }

        header('Content-Type: application/json; charset=utf-8');

        $arr = [
            'status' => $statusCode,
            'message' => $message,
            'statusStr' => $status,
        ];

        if (!empty($data)) {
            $arr['data'] = $data;
        }

        $jsonResponse = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

        if ($jsonResponse === false) {
            throw new ServerErrorHttpException(Application::t('Internal error on the server. Contact the administrator.'));
        }

        return $jsonResponse;
    }

}