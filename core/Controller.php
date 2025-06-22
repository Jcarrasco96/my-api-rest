<?php

namespace MyApiRest\core;

use MyApiRest\attributes\ControllerMethod;
use MyApiRest\attributes\ControllerPermission;
use MyApiRest\attributes\ControllerRateLimit;
use MyApiRest\exceptions\BadRequestHttpException;
use MyApiRest\exceptions\ForbiddenHttpException;
use MyApiRest\exceptions\MethodNotAllowedHttpException;
use MyApiRest\exceptions\RequestEntityTooLargeHttpException;
use MyApiRest\exceptions\TooManyRequestsHttpException;
use MyApiRest\exceptions\UnsupportedMediaTypeHttpException;
use MyApiRest\validators\RateLimitChecker;
use MyApiRest\validators\ValidateContentSize;
use MyApiRest\validators\ValidateContentType;
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
     * @throws RequestEntityTooLargeHttpException
     * @throws BadRequestHttpException
     * @throws UnsupportedMediaTypeHttpException
     * @throws TooManyRequestsHttpException
     * @throws ForbiddenHttpException
     * @throws MethodNotAllowedHttpException
     */
    protected function beforeAction(ReflectionMethod $method): void
    {
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // 1 Rate Limiting
        $rateLimitAttr = $method->getAttributes(ControllerRateLimit::class)[0] ?? null;
        if ($rateLimitAttr) {
            $rateLimit = $rateLimitAttr->newInstance();
            RateLimitChecker::check($method->name, $rateLimit->limit, $rateLimit->seconds);
        }

        // 2 Validate HTTP methods
        $allowedMethods = ['OPTIONS'];
        foreach ($method->getAttributes(ControllerMethod::class) as $attr) {
            $instance = $attr->newInstance();
            $allowedMethods = array_merge($allowedMethods, $instance->methods);

            if (!in_array($requestMethod, $instance->methods)) {
                throw new MethodNotAllowedHttpException("Invalid request method $requestMethod.");
            }
        }

        header("Access-Control-Allow-Methods: " . implode(',', array_unique($allowedMethods)));

        // 3 Validate Content-Type y Size
        if ($requestMethod !== 'GET' && $requestMethod !== 'DELETE') {
            ValidateContentType::validate([
                'application/json',
                'application/x-www-form-urlencoded',
                'multipart/form-data'
            ]);
            ValidateContentSize::validate();
        }

        // 4. Validate permissions
        $permissions = [];
        foreach ($method->getAttributes(ControllerPermission::class) as $attr) {
            $permissions = array_merge($permissions, $attr->newInstance()->permissions);
        }

        if (!$this->checkSpecialPermissions($permissions) && !AuthorizationToken::userHasAccess($permissions)) {
            throw new ForbiddenHttpException(Application::t('You do not have permission to access this page.'));
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    protected function checkSpecialPermissions(array $permissions): bool
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
            $token = AuthorizationToken::token();

            if (!empty($token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws UnsupportedMediaTypeHttpException
     * @throws MethodNotAllowedHttpException
     * @throws RequestEntityTooLargeHttpException
     * @throws BadRequestHttpException
     * @throws TooManyRequestsHttpException
     * @throws ReflectionException
     * @throws ForbiddenHttpException
     */
    public function createAction(string $methodName, array $params = []): array
    {
        $methodNameNormalized = $this->normalizeAction($methodName);

        if (method_exists($this, $methodNameNormalized)) {
            $method = new ReflectionMethod($this, $methodNameNormalized);

            if ($method->isPublic()) {
                $this->beforeAction($method);
                return $method->invokeArgs($this, $params);
            }

            throw new BadRequestHttpException(Application::t('The requested method must be public.'));
        }

        throw new BadRequestHttpException(Application::t('The requested method does not exist.'));
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