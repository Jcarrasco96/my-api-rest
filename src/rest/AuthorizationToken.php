<?php

namespace SimpleApiRest\rest;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SimpleApiRest\core\BaseApplication;
use SimpleApiRest\exceptions\BadRequestHttpException;
use SimpleApiRest\models\UserIdentity;

class AuthorizationToken
{

    /**
     * @throws BadRequestHttpException
     */
    public static function dataToken(): array
    {
        $token = self::token();

        $payload = JWT::decode($token, new Key(BaseApplication::$config['jwtSecretKey'], 'HS256'));

        if (!isset($payload->_id) || !isset($payload->_username)) {
            throw new BadRequestHttpException("You must provide a valid token.");
        }

        $returnArray = [
            '_id' => $payload->_id,
            '_username' => $payload->_username,
        ];

        if (isset($payload->exp)) {
            $returnArray['exp'] = $payload->exp;
        }

        return $returnArray;
    }

    /**
     * @throws BadRequestHttpException
     */
    public static function checkAccess(string $itemName): bool
    {
        $data = self::dataToken();

        if (empty(BaseApplication::$config['userModel'])) {
            throw new BadRequestHttpException("You must provide a valid User class.");
        }

        return UserIdentity::can($data['_id'], $itemName);
    }

    /**
     * @throws BadRequestHttpException
     */
    public static function token(): string
    {
        $headers = array_change_key_case(getallheaders());

        if (!isset($headers['authorization'])) {
            throw new BadRequestHttpException('Header Authorization not found.');
        }

        return trim(str_replace('Bearer ', '', $headers['authorization']));
    }

    public static function createToken(array $data): string
    {
        return JWT::encode([
            '_id' => $data['id'],
            '_username' =>  $data['username'],
        ], BaseApplication::$config['jwtSecretKey'], 'HS256');
    }

    /**
     * @throws BadRequestHttpException
     */
    public static function userHasAccess(array $userRoles): bool {
        $userRoles = array_values(array_filter($userRoles, fn($v) => !in_array($v, ['*', '@', '?'])));

        foreach ($userRoles as $itemName) {
            if (self::checkAccess($itemName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws BadRequestHttpException
     */
    public static function userToken(): UserIdentity
    {
        $data = self::dataToken();

        $userModel = BaseApplication::$config['userModel'];

        return $userModel::findById($data['_id']);
    }

}