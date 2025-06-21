<?php

namespace MyApiRest\core;

use MyApiRest\attributes\ControllerMethod;
use MyApiRest\attributes\ControllerPermission;
use MyApiRest\exceptions\BadRequestHttpException;
use MyApiRest\exceptions\ForbiddenHttpException;
use MyApiRest\exceptions\MethodNotAllowedHttpException;
use ReflectionException;
use ReflectionMethod;

abstract class Controller
{

    protected array $data;

    public function __construct()
    {
        $input = file_get_contents('php://input');

        $data = json_decode($input, true);

        $this->data = is_array($data) ? $data : [];
    }

    /**
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws MethodNotAllowedHttpException
     */
    protected function beforeAction(ReflectionMethod $method): void
    {
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

        foreach ($method->getAttributes(ControllerMethod::class) as $attr) {
            if (!in_array($requestMethod, $attr->newInstance()->methods)) {
                throw new MethodNotAllowedHttpException("Invalid request method $requestMethod.");
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

        throw new ForbiddenHttpException(Application::t('You do not have permission to access this page.'));
    }

    /**
     * @throws BadRequestHttpException
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
     * @throws MethodNotAllowedHttpException
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws ReflectionException
     */
    public function createAction(string $methodName, array $params = []): void
    {
        $methodNameNormalized = $this->normalizeAction($methodName);

        if (method_exists($this, $methodNameNormalized)) {
            $method = new ReflectionMethod($this, $methodNameNormalized);

            if ($method->isPublic()) {
                $this->beforeAction($method);
                echo $method->invokeArgs($this, $params);
            }
        }
    }

    private function normalizeAction(string $methodName): string
    {
        $methodName = preg_replace_callback('/-([a-z])/', fn($m) => strtoupper($m[1]), strtolower($methodName));
        return 'action' . ucfirst($methodName);
    }

    public function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === ControllerMethod::ROUTER_POST;
    }

}