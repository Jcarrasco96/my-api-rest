<?php

namespace MAR\helpers;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MAR\core\MyApiRestApp;
use MAR\models\UserIdentity;

class TokenValidator
{

    /**
     * @throws Exception
     */
    public static function dataToken(): array
    {
        $token = TokenValidator::token();

        $payload = JWT::decode($token, new Key(MyApiRestApp::$config['jwtSecretKey'], 'HS256'));

        if (!isset($payload->id)) {
            throw new Exception("You must provide a valid token.", 400);
        }

        $returnArray = [
            'user_id' => $payload->id,
        ];

        if (isset($payload->exp)) {
            $returnArray['exp'] = $payload->exp;
        }

        return $returnArray;
    }

    /**
     * @throws Exception
     */
    public static function checkAccess(string $itemName): bool
    {
        $data = self::dataToken();

        if (empty(MyApiRestApp::$config['userModel'])) {
            throw new Exception("You must provide a valid User class.", 400);
        }

        $userModel = MyApiRestApp::$config['userModel'];
        /** @var UserIdentity $userModel */

        return $userModel::can($data['user_id'], $itemName);
    }

    /**
     * @throws Exception
     */
    public static function token(): string
    {
        $headers = array_change_key_case(getallheaders());

        if (!isset($headers['authorization'])) {
            throw new Exception('Header Authorization not found.', 400);
        }

        return trim(str_replace('Bearer ', '', $headers['authorization']));
    }

    /**
     * @throws Exception
     */
    public static function userHasAccess(array $userRoles): bool {
        foreach ($userRoles as $itemName) {
            if (self::checkAccess($itemName)) {
                return true;
            }
        }
        return false;
    }

}