<?php

namespace SimpleApiRest\rest;

class JsonResponse
{

    public static function response(array $data): array
    {
        if (isset($data["status"])) {
            http_response_code($data["status"]);
            unset($data["status"]);
        }

        header('Content-Type: application/json; charset=utf-8');

        $arr = [
            'message' => $data['message'] ?? '',
        ];

        unset($data['message']);

        if (!empty($data)) {
//            $arr['data'] = $data;
            $arr = array_merge($arr, $data);
        }

        return $arr;
    }

}