<?php

namespace SimpleApiRest\console;

use ReflectionClass;

class CLIController extends BaseCLI
{

    public static function generate(string $table, bool $override): void
    {
        echo PHP_EOL . CLI::clog("GENERATING CONTROLLERS", 'g') . PHP_EOL;

        if (!$override) {
            echo PHP_TAB . "You can use " . CLI::clog('-fc', 'c') . " for override existing class." . PHP_EOL;
        }

        $repositoryShortName = ucfirst(self::camelCase($table)) . 'Repository';
        $controllerClassName = str_replace('Repository', 'Controller', $repositoryShortName);

        $existFile = file_exists(APP_CONTROLLERS_FOLDER . $controllerClassName . '.php');

        if ($existFile && !$override) {
            echo PHP_TAB . "Controller " . CLI::clog($controllerClassName, 'y') . " already exists!" . PHP_EOL;
            return;
        }

        $repositoryClassName = CLI::$config['repositoryNamespace'] . '\\' . $repositoryShortName;

        if (!class_exists($repositoryClassName)) {
            echo CLI::clog("Error: Class $repositoryClassName not found.", 'r') . PHP_EOL;
            return;
        }

        $modelClassName = str_replace('Repository', '', $repositoryShortName);

        $controllerContent = "<?php" . PHP_EOL . PHP_EOL;
        $controllerContent .= "namespace " . CLI::$config['controllerNamespace'] . ";" . PHP_EOL . PHP_EOL;
        $controllerContent .= "use " . CLI::$config['repositoryNamespace'] . "\\$repositoryShortName;" . PHP_EOL;
//            $controllerContent .= "use " . CLI::$config['modelNamespace'] . "\\$modelClassName;" . PHP_EOL;
        $controllerContent .= "use SimpleApiRest\\attributes\\Route;" . PHP_EOL . PHP_EOL;
        $controllerContent .= "class $controllerClassName {" . PHP_EOL . PHP_EOL;
        $controllerContent .= PHP_TAB . "private $repositoryShortName \$repository;" . PHP_EOL . PHP_EOL;
        $controllerContent .= PHP_TAB . "public function __construct() {" . PHP_EOL;
        $controllerContent .= PHP_TAB . PHP_TAB . "\$this->repository = new $repositoryShortName();" . PHP_EOL;
        $controllerContent .= PHP_TAB . "}" . PHP_EOL . PHP_EOL;

        $reflection = new ReflectionClass($repositoryClassName);

        $methods = self::generateMethods($reflection, lcfirst($modelClassName));

        foreach ($methods as $methodCode) {
            $controllerContent .= PHP_TAB . $methodCode . PHP_EOL;
        }

        $controllerContent .= "}" . PHP_EOL . PHP_EOL;

        file_put_contents(APP_CONTROLLERS_FOLDER . $controllerClassName . '.php', $controllerContent);
        echo PHP_TAB . "Controller " . CLI::clog($controllerClassName, 'c') . " generated successfully!" . PHP_EOL;
    }

    private static function generateMethods(ReflectionClass $reflection, string $routes): array
    {
        $methods = [];

        foreach ($reflection->getMethods() as $method) {
            if (!$method->isPublic() || $method->getName() == '__construct') {
                continue;
            }

            $methodName = $method->getName();

            if (stripos($methodName, 'findAll') !== false) {
                $httpMethod = 'GET';
                $route = "$routes";
                $parameters = '';
            } elseif (stripos($methodName, 'findById') !== false) {
                $httpMethod = 'GET';
                $route = "$routes/{id}";
                $parameters = "\$id";
//            } elseif (stripos($methodName, 'getMasterWithDetails') !== false) {
//                $httpMethod = 'GET';
//                $route = "$routes/getmasterdetail";
//                $parameters = "";
//            } elseif (stripos($methodName, 'saveMasterDetail') !== false) {
//                $httpMethod = 'POST';
//                $route = "$routes/savemasterdetail";
//                $parameters = "\$data";
//            } elseif (stripos($methodName, 'atualizaDetail') !== false) {
//                $httpMethod = 'PUT';
//                $route = "$routes/{id}/updatedetail";
//                $parameters = "\$id";
//            } elseif (stripos($methodName, 'excluiDetail') !== false) {
//                $httpMethod = 'DELETE';
//                $route = "$routes/{id}/deletedetail";
//                $parameters = "\$id";
            } elseif (stripos($methodName, 'update') !== false) {
                $httpMethod = 'PUT';
                $route = "$routes/{id}";
                $parameters = "\$id, \$data";
            } elseif (stripos($methodName, 'delete') !== false) {
                $httpMethod = 'DELETE';
                $route = "$routes/{id}";
                $parameters = "\$id";
            } else {
                $httpMethod = 'POST';
                $route = "$routes";
                $parameters = "\$data";
            }

            $methodContent = "#[Route('$route', [Route::ROUTER_$httpMethod])]" . PHP_EOL;
            $methodContent .= PHP_TAB . "public function $methodName($parameters): array" . PHP_EOL;
            $methodContent .= PHP_TAB . "{" . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . "\$result = \$this->repository->$methodName($parameters);" . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . "return [" . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . PHP_TAB . "'Success.'," . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . PHP_TAB . "'result' => \$result," . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . "];" . PHP_EOL;
            $methodContent .= PHP_TAB . "}" . PHP_EOL;

            $methods[] = $methodContent;
        }

        return $methods;
    }

}