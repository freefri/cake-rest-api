<?php

namespace RestApi\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;
use RestApi\Controller\Component\ApiRestCorsComponent;

class RestApiErrorController extends Controller
{
    public function initialize(): void
    {
        ApiRestCorsComponent::load($this);
    }

    public function render(?string $template = null, ?string $layout = null): Response
    {
        $this->name = 'Error';
        $this->response = $this->response->withStringBody(json_encode($this->_getViewVars()));
        $this->response = $this->response->withType('json');
        return $this->response;
    }

    private function _getViewVars(): array
    {
        return $this->viewBuilder()->getVars();
    }
}
