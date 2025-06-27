<?php

namespace SimpleApiRest\console;

use DirectoryIterator;
use ReflectionClass;

class CLIController extends BaseCLI
{

    public static function generate(bool $override): void
    {

        echo PHP_EOL . CLI::clog("GENERATING CONTROLLERS", 'g') . PHP_EOL;

        if (!$override) {
            echo PHP_TAB . "You can use " . CLI::clog('-fc', 'c') . " for override existing class." . PHP_EOL;
        }

        foreach (new DirectoryIterator(APP_REPOSITORY_FOLDER) as $fileInfo) {
            /** @var DirectoryIterator $fileInfo */

            if ($fileInfo->isDot() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $repositoryShortName = $fileInfo->getBasename('.php');
            $controllerClassName = str_replace('Repository', 'Controller', $repositoryShortName);

            $existFile = file_exists(APP_CONTROLLERS_FOLDER . $controllerClassName . '.php');

            if ($existFile && !$override) {
                echo PHP_TAB . "Controller " . CLI::clog($controllerClassName, 'y') . " already exists!" . PHP_EOL;
                continue;
            }

            $repositoryClassName = CLI::$config['repositoryNamespace'] . '\\' . $fileInfo->getBasename('.php');

            if (!class_exists($repositoryClassName)) {
                echo "Error: Class $repositoryClassName not found." . PHP_EOL;
                continue;
            }

            $modelClassName = str_replace('Repository', '', $repositoryShortName);

            $reflection = new ReflectionClass($repositoryClassName);

            $methods = self::generateMethods($reflection, lcfirst($modelClassName));

            $controllerContent = "<?php" . PHP_EOL . PHP_EOL;
            $controllerContent .= "namespace " . CLI::$config['controllerNamespace'] . ";" . PHP_EOL . PHP_EOL;
            $controllerContent .= "use " . CLI::$config['repositoryNamespace'] . "\\$repositoryShortName;" . PHP_EOL;
//            $controllerContent .= "use " . CLI::$config['modelNamespace'] . "\\$modelClassName;" . PHP_EOL;
            $controllerContent .= "use SimpleApiRest\\router\\Router;" . PHP_EOL . PHP_EOL;
            $controllerContent .= "class $controllerClassName {" . PHP_EOL . PHP_EOL;
            $controllerContent .= PHP_TAB . "private $repositoryShortName \$repository;" . PHP_EOL . PHP_EOL;
            $controllerContent .= PHP_TAB . "public function __construct() {" . PHP_EOL;
            $controllerContent .= PHP_TAB . PHP_TAB . "\$this->repository = new $repositoryShortName();" . PHP_EOL;
            $controllerContent .= PHP_TAB . "}" . PHP_EOL . PHP_EOL;

            foreach ($methods as $methodCode) {
                $controllerContent .= PHP_TAB . $methodCode . PHP_EOL;
            }

            $controllerContent .= "}" . PHP_EOL . PHP_EOL;

            file_put_contents(APP_CONTROLLERS_FOLDER . $controllerClassName . '.php', $controllerContent);
            echo PHP_TAB . "Controller " . CLI::clog($controllerClassName, 'c') . " generated successfully!" . PHP_EOL;
        }
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
                $route = "/$routes";
                $parameters = '';
            } elseif (stripos($methodName, 'findById') !== false) {
                $httpMethod = 'GET';
                $route = "/$routes/{id}";
                $parameters = "\$id";
//            } elseif (stripos($methodName, 'getMasterWithDetails') !== false) {
//                $httpMethod = 'GET';
//                $route = "/$routes/getmasterdetail";
//                $parameters = "";
//            } elseif (stripos($methodName, 'saveMasterDetail') !== false) {
//                $httpMethod = 'POST';
//                $route = "/$routes/savemasterdetail";
//                $parameters = "\$data";
//            } elseif (stripos($methodName, 'atualizaDetail') !== false) {
//                $httpMethod = 'PUT';
//                $route = "/$routes/{id}/updatedetail";
//                $parameters = "\$id";
//            } elseif (stripos($methodName, 'excluiDetail') !== false) {
//                $httpMethod = 'DELETE';
//                $route = "/$routes/{id}/deletedetail";
//                $parameters = "\$id";
            } elseif (stripos($methodName, 'update') !== false) {
                $httpMethod = 'PUT';
                $route = "/$routes/{id}";
                $parameters = "\$id, \$data";
            } elseif (stripos($methodName, 'delete') !== false) {
                $httpMethod = 'DELETE';
                $route = "/$routes/{id}";
                $parameters = "\$id";
            } else {
                $httpMethod = 'POST';
                $route = "/$routes";
                $parameters = "\$data";
            }

            $methodContent = "#[Router('$route', ['$httpMethod'])]" . PHP_EOL;
            $methodContent .= PHP_TAB . "public function $methodName($parameters) {" . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . "\$result = \$this->repository->$methodName($parameters);" . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . "if (!is_array(\$result) && !\$result['success']) {" . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . PHP_TAB. "return [null, '', \$result['message'], \$result['success']];" . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . "}" . PHP_EOL;
            $methodContent .= PHP_TAB . PHP_TAB . "return [\$result, 'Operação realizada com sucesso.', '', true];" . PHP_EOL;
            $methodContent .= PHP_TAB . "}" . PHP_EOL;

            $methods[] = $methodContent;
        }

        return $methods;
    }

}