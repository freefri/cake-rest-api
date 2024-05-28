<?php

declare(strict_types = 1);

namespace RestApi\Controller;

use Cake\Controller\Component;
use Cake\Core\Configure;
use RestApi\Lib\RestPlugin;

class SwaggerJsonController extends RestApiController
{
    public function isPublicController(): bool
    {
        return true;
    }

    protected function getList()
    {
        $dir = ROOT . DS . 'plugins' . DS . $this->_getBaseNamespace() . DS . RestPlugin::swaggerPath() . DS;
        $readerClass = Configure::read('Swagger.readerClass');
        if (!$readerClass) {
            $readerClass = \RestApi\Lib\Swagger\SwaggerReader::class;
        }
        $reader = new $readerClass();
        $paths = $reader->readFiles($dir);
        $content = $reader->getInfo($paths);

        $this->response = $this->response->withType('application/json');
        $this->response = $this->response->withStringBody(json_encode($content));
        $this->return = $this->response;
    }

    private function _getBaseNamespace()
    {
        return explode('\\', get_called_class())[0] ?? '';
    }

    protected function _loadOAuthServerComponent(): Component
    {
    }

    protected function _setUserLang(): void
    {
    }

    protected function _setLanguage(): void
    {
    }

    protected function getLocalOauth()
    {
    }
}
