<?php

namespace MAR\core;

use Exception;
use MAR\attributes\ControllerMethod;
use MAR\attributes\ControllerPermission;
use MAR\attributes\RequireToken;
use MAR\helpers\TokenValidator;
use ReflectionException;
use ReflectionMethod;

abstract class BaseController
{

    protected float $startTime;

    /**
     * @throws Exception
     */
    protected function beforeAction(ReflectionMethod $method): void
    {
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

        foreach ($method->getAttributes(ControllerMethod::class) as $attr) {
            if (!in_array($requestMethod, $attr->newInstance()->methods)) {
                throw new Exception("Invalid request method $requestMethod.", 405);
            }
        }

        header("Access-Control-Allow-Methods: OPTIONS," . $requestMethod);
        header("Allow: OPTIONS," . $requestMethod);

        $permissions = [];
        foreach ($method->getAttributes(ControllerPermission::class) as $attr) {
            $permissions = array_merge($permissions, $attr->newInstance()->permissions);
        }

        if ($this->checkSpecialPermissions($permissions)) {
            return;
        }

        if (TokenValidator::userHasAccess($permissions)) {
            return;
        }

        throw new Exception(MyApiRestApp::t('You do not have permission to access this page.'), 403);
    }

    /**
     * @throws Exception
     */
    protected function checkSpecialPermissions(array &$permissions): bool
    {
        if (in_array('?', $permissions)) {
            $headers = array_change_key_case(getallheaders());

            if (!isset($headers['authorization'])) {
                return true;
            }
        }

        if (in_array('*', $permissions)) {
            return true;
        }

        if (in_array('@', $permissions)) {
            $token = TokenValidator::token();

            if (!empty($token)) {
                return true;
            }
        }

        $permissions = array_values(array_filter($permissions, fn($v) => !in_array($v, ['*', '@', '?'])));

        return false;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function createAction($methodName, $params = []): bool
    {
        $this->startTime = microtime(true);

        $methodNameNormalized = $this->normalizeAction($methodName);

        if (method_exists($this, $methodNameNormalized)) {
            $method = new ReflectionMethod($this, $methodNameNormalized);

            if ($method->isPublic()) {
                $this->beforeAction($method);
                echo $method->invokeArgs($this, $params);
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected function asJson(array $params = []): string
    {
        if (isset($params["statusCode"])) {
            http_response_code($params["statusCode"]);
            unset($params["statusCode"]);
        }

        header('Content-Type: application/json; charset=utf-8');

        $jsonResponse = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

        if ($jsonResponse === false) {
            throw new Exception(MyApiRestApp::t('Internal error on the server. Contact the administrator.'), 500);
        }

        return $jsonResponse;
    }

    private function normalizeAction($methodName): ?string
    {
        $methodName = preg_replace_callback('/-([a-z])/', fn($m) => strtoupper($m[1]), strtolower($methodName));
        return 'action' . ucfirst($methodName);
    }

    protected function jsonInput(): ?array
    {
        $input = file_get_contents('php://input');

        $data = json_decode($input, true);

        return is_array($data) ? $data : null;
    }

}