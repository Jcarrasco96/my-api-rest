<?php

namespace SimpleApiRest\rest;

class JsonResponse
{

    public static function response(array $data): array
    {
        $success = true;

        if (isset($data["status"])) {
            http_response_code($data["status"]);

            if ($data['status'] < 200 || $data['status'] > 299) {
                $success = false;
            }

            unset($data["status"]);
        }

        header('Content-Type: application/json; charset=utf-8');

        $arr = [
            'success' => $success,
            'message' => $data['message'] ?? '',
        ];

        unset($data['message']);

        if (!empty($data)) {
            $arr['data'] = $data;
        }

        return $arr;
    }

}