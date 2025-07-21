<?php
declare(strict_types=1);

namespace RestApi\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;

class ApiRestCorsComponent extends Component
{
    public static function load(Controller $controller, $AppCorsClassName = null): ApiRestCorsComponent
    {
        if (!$AppCorsClassName) {
            $AppCorsClassName = env('CORS_CLASS_NAME');
        }
        if (!$AppCorsClassName) {
            $AppCorsClassName = self::getCorsClassName();
        }
        $controller->ApiCors = $controller->loadComponent($AppCorsClassName);
        return $controller->ApiCors;
    }

    private static function getCorsClassName(): string
    {
        $className = Configure::read('App.Cors.ClassName');
        $err = 'ApiRestCorsComponent Error! Configuration App.Cors.ClassName in config/app.php';
        if (!$className) {
            return ApiRestCorsComponent::class;
        }
        if (!class_exists($className)) {
            die($err . ' must be a class: '.$className);
        }
        return $className;
    }

    protected function getAllowedCors(): array
    {
        $cors = Configure::read('App.Cors.AllowOrigin');
        if (!$cors) {
            return [];
        }
        return $cors;
    }

    public function beforeFilter(EventInterface $event)
    {
        /** @var Controller $controller */
        $controller = $event->getSubject();
        if ($controller) {
            $response = $controller->getResponse();
            $response->withDisabledCache();

            $responseBuilder = $response->cors($controller->getRequest());

            $allowedCors = $this->getAllowedCors();
            $currentOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $isAnyOriginAllowed = ($allowedCors[0] ?? null) === '*';
            $isSameCors = in_array($currentOrigin, $allowedCors);
            if ($currentOrigin && ($isAnyOriginAllowed || $isSameCors)) {
                $responseBuilder->allowOrigin([$currentOrigin])
                    ->allowCredentials();
            }
            if ($controller->getRequest()->is('options')) {
                $responseBuilder
                    ->allowMethods(['POST', 'GET', 'PATCH', 'PUT', 'DELETE'])
                    ->allowHeaders([
                        'Authorization',
                        'Content-Type',
                        'Accept-Language',
                        'X-Experience-API-Version',
                        'X-Whitelabel',
                        'Baggage',
                    ])
                    ->maxAge(3600);
                $response = $responseBuilder->build();
                $controller->setResponse($response);
                return $response;
            }
            $response = $responseBuilder->build();
            $controller->setResponse($response);
        }
    }
}
