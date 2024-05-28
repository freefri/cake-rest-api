<?php

declare(strict_types = 1);

namespace RestApi\Controller;

use Cake\Core\Configure;
use RestApi\Lib\RestPlugin;
use RestApi\Lib\Swagger\SwaggerReader;

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
        $paths = $reader->readFiles($dir);
        $content = $this->getContent($reader, $paths);

        $this->response = $this->response->withType('application/json');
        $this->response = $this->response->withStringBody(json_encode($content));
        $this->return = $this->response;
    }

    private function getDirectory(): string
    {
        return ROOT . DS . 'plugins' . DS . $this->_getBaseNamespace() . DS . RestPlugin::swaggerPath() . DS;
    }

    protected function getReader(): SwaggerReader
    {
        $readerClass = Configure::read('Swagger.readerClass');
        if (!$readerClass) {
            $readerClass = SwaggerReader::class;
        }
        $reader = new $readerClass();
        return $reader;
    }

    protected function getContent(SwaggerReader $reader, array $paths): array
    {
        return $reader->getInfo($paths);
    }

    private function _getBaseNamespace()
    {
        return explode('\\', get_called_class())[0] ?? '';
    }
}
