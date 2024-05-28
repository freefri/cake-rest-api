<?php

declare(strict_types = 1);

namespace RestApi\Lib;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Http\Exception\InternalErrorException;
use Cake\Routing\RouteBuilder;

abstract class RestPlugin extends BasePlugin
{
    protected abstract function routeConnectors(RouteBuilder $builder): void;

    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            $this->name,
            ['path' => $this->getRoutePathGeneric($this->getBaseNamespace())],
            function (RouteBuilder $builder) {
                $this->routeConnectors($builder);
                $route = '/' . $this->pluginPath() . '/' . $this->swaggerPath();
                $builder->connect($route . '/*', \RestApi\Controller\SwaggerJsonController::route());
                $builder->connect($route . '/ui/*', \Swagger\Controller\SwaggerUiController::route());
            }
        );
        parent::routes($routes);
    }

    public static function swaggerPath(): string
    {
        return 'swagger-openapi';
    }

    protected function pluginPath(): string
    {
        return strtolower($this->getBaseNamespace());
    }

    public static function getMigrationLoader(): array
    {
        $className = get_called_class();
        return ['plugin' => (new $className)->getName()];
    }

    public static function getTablePrefix(): string
    {
        return self::getTablePrefixGeneric(self::getBaseNamespace());
    }

    public static function getTablePrefixGeneric(string $pluginNamespace): string
    {
        return self::getGenericPluginConfig('tablePrefix', $pluginNamespace);
    }

    public static function getRoutePath(): string
    {
        return self::getRoutePathGeneric(self::getBaseNamespace());
    }

    private static function getRoutePathGeneric(string $pluginNamespace): string
    {
        return self::getGenericPluginConfig('routePath', $pluginNamespace);
    }

    private static function getGenericPluginConfig(string $key, string $pluginNamespace): string
    {
        if ($pluginNamespace === 'RestApi') {
            throw new InternalErrorException('RestPlugin::getTablePrefix() must be called from child class');
        }
        $res = Configure::read($pluginNamespace . 'Plugin.' . $key);
        if (!$res) {
            return '';
        }
        return $res;
    }

    public static function getBaseNamespace()
    {
        return explode('\\', get_called_class())[0] ?? '';
    }
}
