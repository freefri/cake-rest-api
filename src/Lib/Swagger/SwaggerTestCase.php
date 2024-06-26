<?php

namespace RestApi\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Utility\Inflector;
use RestApi\Controller\RestApiErrorController;

class SwaggerTestCase implements \JsonSerializable
{
    private Controller $_controller;
    private array $_request;
    private Response $_response;
    private \Exception $_exception;
    private string $_cachedRoute = '';
    private $_lastRoute;

    public function __construct(Controller $controller, array $request, Response $res, string $lastRoute = null)
    {
        $this->_controller = $controller;
        $this->_request = $request;
        $this->_response = $res;
        $this->_lastRoute = $lastRoute;
        $this->_exception = new \Exception('');
    }

    public function getDescription(): string
    {
        $fn = $this->_exception->getTrace()[4]['function'];
        $humanize = Inflector::humanize(Inflector::underscore($fn));
        $humanize = str_replace('Test ', '', str_replace('  ', ' ', $humanize));
        return mb_substr($humanize, 0, 1) . mb_strtolower(mb_substr($humanize, 1));
    }

    private function _getMatchedRoute(): ?string
    {
        $matchedRoute = $this->_controller->getRequest()->getParam('_matchedRoute');
        if (!$matchedRoute) {
            return $this->_lastRoute;
        }
        return $matchedRoute;
    }

    public function getRoute(): string
    {
        if ($this->_cachedRoute) {
            return $this->_cachedRoute;
        }
        $matchedRoute = $this->_getMatchedRoute();
        $mainRoute = str_replace('*', '', $matchedRoute);
        $exploded = explode('/', $mainRoute);
        $lastInRoute = array_pop($exploded);
        if ($lastInRoute === '') {
            $lastInRoute = array_pop($exploded);
        }
        $url = $this->getRequest()['url'];
        $explodedUrl = explode('/' . $lastInRoute . '/', $url);
        $this->_cachedRoute = $mainRoute;
        if (count($explodedUrl) >= 2) {
            $last = array_pop($explodedUrl);
            if ($last && strpos($last, '/') === false) {
                $this->_cachedRoute = $mainRoute . '{entity_id}';
            }
        }
        return $this->_cachedRoute;
    }

    public function getStatusCode(): int
    {
        return $this->_response->getStatusCode();
    }

    public function getStatusCodeString(): string
    {
        return $this->getStatusCode() . '';
    }

    public function getStatusDescription(): string
    {
        return $this->_response->getReasonPhrase();
    }

    public function getMethod(): string
    {
        return strtolower($this->_request['environment']['REQUEST_METHOD']);
    }

    public function getRequest(): array
    {
        return $this->_request;
    }

    private function getJson(): ?array
    {
        $json = json_decode($this->_response->getBody(), true);
        $resCode = $this->getStatusCode();
        if ($resCode > 399) {
            if (isset($json['exception'])) {
                unset($json['message']);
            }
            unset($json['trigger']);
            unset($json['exception']);
            unset($json['file']);
            unset($json['line']);
            unset($json['details']);
        }
        if (isset($json['data'][0])) {
            $json['data'] = [$json['data'][0]];
        }
        return $json;
    }

    public function toMd5(): string
    {
        return md5(json_encode($this->toArray()));
    }

    public function toArray(): array
    {
        return [
            'route' => $this->getRoute(),
            'req' => $this->getRequest(),
            //'res' => $res,
            'code' => $this->getStatusCode(),
            'json' => $this->getJson(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function _getPathParams(): array
    {
        $matches = [];
        preg_match_all('/{[a-zA-Z_]+}/', $this->getRoute(), $matches);
        if (!isset($matches[0][0])) {
            return [];
        }
        $toRet = [];
        foreach ($matches[0] as $param) {
            $paramWithoutBraces = substr($param, 1, strlen($param) - 2);
            $toRet[] = [
                'description' => '',
                'in' => 'path',
                'name' => $paramWithoutBraces,
                'required' => true,
                'schema' => ['type' => 'integer'],
            ];
        }
        return $toRet;
    }

    private function _getQueryParams(): array
    {
        $toRet = [];
        foreach ($this->getRequest()['query'] ?? [] as $param => $example) {
            $toRet[] = [
                'description' => '',
                'in' => 'query',
                'name' => $param,
                'example' => $example,
                'required' => false,
                'schema' => ['type' => 'string'],
            ];
        }
        return $toRet;
    }

    private function _isPublicController(): bool
    {
        if ($this->_controller instanceof RestApiErrorController) {
            return true;
        }
        if (!method_exists($this->_controller, 'isPublicController')) {
            return false;
        }
        return $this->_controller->isPublicController();
    }

    private function _getHeaderParams(): array
    {
        $toRet = [];
        if (!$this->_isPublicController()) {
            $toRet[] = [
                'description' => 'Auth token',
                'in' => 'header',
                'name' => 'Authentication',
                'example' => 'Bearer xxxxxx',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
        }
        $acceptLanguage = Configure::read('Swagger.acceptLanguage');
        if ($acceptLanguage !== false) {
            if (!$acceptLanguage) {
                $acceptLanguage = 'Language letter code (depending on setup: en, es, de, ar, eng, spa, es_AR, en_US)';
            }
            $toRet[] = [
                'description' => $acceptLanguage,
                'in' => 'header',
                'name' => 'Accept-Language',
                'example' => 'en',
                'required' => false,
                'schema' => ['type' => 'string'],
            ];
        }
        return $toRet;
    }

    public function getParams(): array
    {
        return array_merge($this->_getPathParams(), $this->_getQueryParams(), $this->_getHeaderParams());
    }

    private function _getItems($data): array
    {
        if (!is_array($data[0])) {
            return $this->_getProp($data[0]);
        }
        if (isset($data[0][0])) {
            return [
                'type' => 'array',
                'items' => $this->_getItems($data[0]),
            ];
        }
        foreach ($data[0] as $property => $value) {
            $properties[$property] = $this->_getProp($value, $property);
        }
        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    public function getResponseSchema(): ?array
    {
        if ($this->getJson() === null) {
            return null;
        }
        $json = $this->getJson()['data'] ?? null;
        $properties = [];
        if ($json) {
            $isArray = isset($json[0]);
            if ($isArray) {
                return [
                    'type' => 'object',
                    'description' => $this->getDescription(),
                    'properties' => [
                        'data' => [
                            'type' => 'array',
                            'items' => $this->_getItems($json),
                        ]
                    ],
                ];
            } else if (is_bool($json)) {
                return [
                    'type' => 'object',
                    'description' => $this->getDescription(),
                    'properties' => [
                        'data' => [
                            'type' => 'boolean',
                            'properties' => $json,
                        ]
                    ],
                ];
            } else {
                foreach ($json as $property => $value) {
                    $properties[$property] = $this->_getProp($value, $property);
                }
                return [
                    'type' => 'object',
                    'description' => $this->getDescription(),
                    'properties' => [
                        'data' => [
                            'type' => 'object',
                            'properties' => $properties,
                        ]
                    ],
                ];
            }
        } else {
            // not json with data
            foreach ($this->getJson() as $property => $value) {
                $properties[$property] = $this->_getProp($value, $property);
            }
            return [
                'type' => 'object',
                'description' => $this->getDescription(),
                'properties' => $properties,
            ];
        }
    }

    public function getRequestSchema(): ?array
    {
        $post = $this->_request['post'] ?? '';
        if (!$post) {
            return null;
        }
        $properties = [];
        $isArray = isset($post[0]);
        if ($isArray) {
            foreach ($post[0] as $property => $value) {
                $properties[$property] = $this->_getProp($value, $property);
            }
            return [
                'type' => 'array',
                'items' => $this->_getItems($post),
            ];
        } else {
            foreach ($post as $property => $value) {
                $properties[$property] = $this->_getProp($value, $property);
            }
            return [
                'type' => 'object',
                'properties' => $properties,
            ];
        }
    }

    private function _getProp($value, string $property = null): array
    {
        if (is_array($value)) {
            if ($value === []) {
                $prop = [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object'
                    ]
                ];
            } else {
                $prop = [
                    'type' => 'string',
                    //'type' => 'object',
                    //'example' => json_encode($value),
                    'example' => str_replace('"', '`', json_encode($value)),
                ];
            }
        } else if (is_numeric($value)) {
            $prop = [
                'type' => 'number',
                'example' => $value + 0,
            ];
        } else if ($value === true || $value === false) {
            $prop = [
                'type' => 'boolean',
                'example' => $value,
            ];
        } else {
            if ($property === 'password') {
                $value = str_repeat('*', mb_strlen($value));
            }
            $prop = [
                'type' => 'string',
                'example' => ''.$value,
            ];
        }
        return $prop;
    }

    public function getControllerName(): string
    {
        return $this->_controller->getName();
    }

    public function getTags(): array
    {
        return [$this->_controller->getName()];
    }

    public function getSecurity(): ?array
    {
        if (!$this->_isPublicController()) {
            return [['bearerAuth' => []]];
        }
        return null;
    }
}
