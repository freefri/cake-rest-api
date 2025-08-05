<?php

declare(strict_types = 1);

namespace RestApi\Controller;

use Cake\Core\Configure;
use RestApi\Lib\RestPlugin;
use RestApi\Lib\Swagger\FileReader\PathReader;
use RestApi\Lib\Swagger\FileReader\SchemaReader;
use RestApi\Lib\Swagger\FileReader\SwaggerReader;
use RestApi\Lib\Swagger\SwaggerFromController;

class SwaggerJsonController extends \Swagger\Controller\SwaggerUiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getData($id)
    {
        if ($id === 'json') {
            $this->_getJson();
        } else {
            parent::getData($id);
        }
    }

    private function _getJson()
    {
        $dir = $this->getDirectory();
        $reader = $this->getReader();
        $paths = $reader->readFiles($dir, new PathReader());
        $componentSchemas = $reader->readFiles($dir . SwaggerFromController::SCHEMA_DIR . DS, new SchemaReader());
        $content = $this->getContent($reader, $paths, $componentSchemas);

        $this->response = $this->response->withType('application/json');
        $this->response = $this->response->withStringBody(json_encode($content));
        $this->return = $this->response;
    }

    private function getDirectory(): string
    {
        if ($this->_getBaseNamespace() === 'App') {
            return ROOT. DS . RestPlugin::swaggerPath() . DS;
        }
        return ROOT . DS . 'plugins' . DS . $this->_getBaseNamespace() . DS . RestPlugin::swaggerPath() . DS;
    }

    protected function getReader(): SwaggerReader
    {
        $readerClass = Configure::read('Swagger.readerClass');// default reader class
        if (!$readerClass) {
            $readerClass = SwaggerReader::class;
        }
        return new $readerClass();
    }

    protected function getContent(SwaggerReader $reader, array $paths, array $schemas = []): array
    {
        $info = $reader->getInfo($paths);
        if ($schemas) {
            $info['components']['schemas'] = $schemas;
        }
        return $info;
    }

    private function _getBaseNamespace()
    {
        return explode('\\', get_called_class())[0] ?? '';
    }
}
