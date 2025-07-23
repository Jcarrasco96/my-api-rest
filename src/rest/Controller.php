<?php

namespace SimpleApiRest\rest;

use ReflectionException;
use ReflectionMethod;
use SimpleApiRest\attributes\Permission;
use SimpleApiRest\attributes\RateLimit;
use SimpleApiRest\exceptions\BadRequestHttpException;
use SimpleApiRest\exceptions\ForbiddenHttpException;
use SimpleApiRest\exceptions\TooManyRequestsHttpException;
use SimpleApiRest\exceptions\UnauthorizedHttpException;
use SimpleApiRest\validators\RateLimitChecker;

abstract class Controller
{

    protected array $data;

    public function __construct()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $this->data = is_array($data) ? $data : [];
    }

    /**
     * @throws TooManyRequestsHttpException
     * @throws ForbiddenHttpException
     */
    protected function beforeAction(ReflectionMethod $method): void
    {
        // 1 Rate Limiting
        $rateLimitAttr = $method->getAttributes(RateLimit::class)[0] ?? null;
        if ($rateLimitAttr) {
            $class = explode('\\', $method->class);
            $className = array_pop($class);

            unset($class);

            $rateLimit = $rateLimitAttr->newInstance();
            RateLimitChecker::check("{$className}_$method->name", $rateLimit->limit, $rateLimit->seconds);
        }

        // 4. Validate permissions
        $permissions = [];
        foreach ($method->getAttributes(Permission::class) as $attr) {
            $permissions = array_merge($permissions, $attr->newInstance()->permissions);
        }

        if (!empty($permissions) && !$this->checkSpecialPermissions($permissions)) {
            throw new ForbiddenHttpException(Rest::t('You do not have permission to access this page.'));
        }
    }

    abstract protected function checkSpecialPermissions(array $permissions): bool;

    /**
     * @throws BadRequestHttpException
     * @throws TooManyRequestsHttpException
     * @throws ReflectionException
     * @throws ForbiddenHttpException
     * @throws UnauthorizedHttpException
     */
    public function createAction(string $methodName, array $params = []): array
    {
        if (method_exists($this, $methodName)) {
            $method = new ReflectionMethod($this, $methodName);

            if ($method->isPublic()) {
                $this->beforeAction($method);
                return $method->invokeArgs($this, $params);
            }

            throw new BadRequestHttpException(Rest::t('The requested method must be public.'));
        }

        throw new BadRequestHttpException(Rest::t('The requested method does not exist.'));
    }

}