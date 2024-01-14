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
        $this->response = parent::render('error_json');
        $this->response = $this->response->withType('json');
        return $this->response;
    }
}
