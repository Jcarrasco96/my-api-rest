<?php

namespace SimpleApiRest\rest;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use SimpleApiRest\attributes\Route;
use SimpleApiRest\core\BaseApplication;
use SimpleApiRest\exceptions\BadRequestHttpException;
use SimpleApiRest\exceptions\MethodNotAllowedHttpException;
use SimpleApiRest\exceptions\RequestEntityTooLargeHttpException;
use SimpleApiRest\exceptions\ServerErrorHttpException;
use SimpleApiRest\exceptions\UnsupportedMediaTypeHttpException;
use SimpleApiRest\validators\ValidateContentSize;
use SimpleApiRest\validators\ValidateContentType;
use SplFileInfo;

class Router
{

    public array $routes = [];

    /**
     * @throws ReflectionException
     * @throws ServerErrorHttpException
     */
    public function loadRoutes(): void
    {
        if (!empty($this->routes)) {
            $this->routes = [];
        }

        $controllerClasses = $this->loadControllerClasses();

        foreach ($controllerClasses as $controllerClass) {
            $this->process($controllerClass);
        }
    }

    /**
     * @throws ReflectionException
     * @throws ServerErrorHttpException
     */
    private function process(string $controllerClass): void
    {
        $controller = new ReflectionClass($controllerClass);
        $methods = $controller->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (!str_starts_with($method->name, 'action')) {
                continue;
            }

            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                /** @var Route $router */
                $router = $attribute->newInstance();
                foreach ($router->methods as $httpMethod) {
                    $version = $router->version;

                    if (isset($this->routes[$httpMethod][$version . '/' . $router->path])) {
                        throw new ServerErrorHttpException("Route $version/$router->path already exists for method $httpMethod.");
                    }

                    $this->routes[$httpMethod][$version . '/' . $router->path] = [$controllerClass, $method->getName()];
                }
            }
        }
    }

    /**
     * @throws ReflectionException
     * @throws MethodNotAllowedHttpException
     * @throws BadRequestHttpException
     * @throws UnsupportedMediaTypeHttpException
     * @throws RequestEntityTooLargeHttpException
     */
    public function resolve(): array
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $pathInfo = trim($_SERVER['PATH_INFO'] ?? '', '/');

        // 2 Validate HTTP methods
        $this->validateHttpMethods($requestMethod, $pathInfo);

        // 3 Validate Content-Type y Size
        if ($requestMethod !== 'GET' && $requestMethod !== 'DELETE') {
            ValidateContentType::validate([
                'application/json',
                'application/x-www-form-urlencoded',
                'multipart/form-data'
            ]);
            ValidateContentSize::validate();
        }

        $data = json_decode(file_get_contents('php://input'), true);

        foreach ($this->routes[$requestMethod] as $route => $action) {
            $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?}/', function ($matches) {
                $regex = $matches[2] ?? '[^/]+';
                return '(?P<' . $matches[1] . '>' . $regex . ')';
            }, $route);

            $pattern = '#^' . $pattern . '$#u';

            if (preg_match($pattern, $pathInfo, $matches)) {
                $controller = new $action[0]();

                $reflectionMethod = new ReflectionMethod($controller, $action[1]);
                $reflectionParameters = $reflectionMethod->getParameters();

                $params = array_filter($matches,
                    fn($key) => is_string($key), ARRAY_FILTER_USE_KEY
                );

                $args = $this->loadArguments($reflectionParameters, $params, $data);

                return $controller->createAction($action[1], $args);
            }
        }

        throw new BadRequestHttpException(BaseApplication::t('The {method} requested method does not exist.', ['method' => $requestMethod]));
    }

    private function methodsForRoute(string $route): array {
        $methods = [];

        foreach ($this->routes as $method => $paths) {
            foreach ($paths as $path => $handler) {
                $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?}/', function ($matches) {
                    $regex = $matches[2] ?? '[^/]+';
                    return '(?P<' . $matches[1] . '>' . $regex . ')';
                }, $path);

                $pattern = '#^' . $pattern . '$#u';

                if (preg_match($pattern, $route)) {
                    $methods[] = $method;
                }
            }
        }

        return $methods;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    private function validateHttpMethods(string $requestMethod, string $pathInfo): void
    {
        if (!isset($this->routes[$requestMethod])) {
            throw new MethodNotAllowedHttpException("Invalid request method $requestMethod.");
        }

        $allowedMethods = array_merge(['OPTIONS'], $this->methodsForRoute($pathInfo));

        if (!in_array($requestMethod, $allowedMethods)) {
            throw new MethodNotAllowedHttpException("Invalid request method $requestMethod.");
        }

        header("Access-Control-Allow-Methods: " . implode(',', array_unique($allowedMethods)));
    }

    private function loadArguments(array $reflectionParameters, array $params, ?array $data): array
    {
        /** @var ReflectionParameter[] $args */
        /** @var ReflectionParameter $param */

        $args = [];
        foreach ($reflectionParameters as $param) {
            $name = $param->getName();
            if (isset($params[$name])) {
                $args[] = $params[$name];
            } elseif ($name === 'data') {
                $args[] = $data;
            } elseif (count($reflectionParameters) == 2) {
                $args[] = $params[$name];
                $args[] = $data;
            } else {
                $args[] = null;
            }
        }

        return $args;
    }

    private function loadControllerClasses(): array
    {
        $controllerClass = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_CONTROLLERS_FOLDER));

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = substr($file->getPathname(), strlen(APP_CONTROLLERS_FOLDER));
                $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);
                $relativePath = substr($relativePath, 0, -4);
                $classNameRelative = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                $className = BaseApplication::$config['controllerNamespace'] . '\\' . $classNameRelative;

                if (class_exists($className)) {
                    $controllerClass[] = $className;
                }
            }
        }

        return $controllerClass;
    }

}