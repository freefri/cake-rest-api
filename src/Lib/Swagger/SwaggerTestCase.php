<?php

namespace RestApi\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Utility\Inflector;
use RestApi\Controller\RestApiErrorController;

class SwaggerTestCase implements \JsonSerializable
{
    private const ENTITY_BRACES = '{entity_id}';
    private Controller $_controller;
    private array $_request;
    private Response $_response;
    private \Exception $_exception;
    private string $_cachedRoute = '';
    private $_lastRoute;
    private $_json;
    private StandardSchemas $schemas;

    public function __construct(Controller $controller, array $request, Response $res, string $lastRoute = null)
    {
        $this->_controller = $controller;
        $this->_request = $request;
        $this->_response = $res;
        $this->_lastRoute = $lastRoute;
        $this->_exception = new \Exception('');
        $this->schemas = new StandardSchemas();
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
        if ($matchedRoute) {
            $mainRoute = str_replace('*', '', $matchedRoute);
        } else {
            $mainRoute = '';
        }
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
                $this->_cachedRoute = $mainRoute . $this->_lastEntityIdName($lastInRoute);
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
        $this->_json = $json;
        return $this->_json;
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
        $map = $this->_getRouteParamsMap();
        $toRet = [];
        foreach ($matches[0] as $param) {
            $paramExample = $map[$param] ?? null;
            $paramType = is_numeric($paramExample) ? 'integer' : 'string';
            $paramExample = is_numeric($paramExample) ? (int)$paramExample : $paramExample;
            $paramWithoutBraces = substr($param, 1, strlen($param) - 2);
            $toAdd = [
                'description' => 'ID in URL',
                'in' => 'path',
                'name' => $paramWithoutBraces,
                'required' => true,
                'schema' => ['type' => $paramType],
            ];
            if ($paramExample) {
                $toAdd['example'] = $paramExample;
            }
            $toRet[] = $toAdd;
        }
        return $toRet;
    }

    private function _getRouteParamsMap(): array
    {
        $matchedRoute = $this->_getMatchedRoute();
        $explodedMatchedRoute = explode('/', $matchedRoute);
        $url = $this->getRequest()['url'];
        $explodedUrl = explode('/', $url);
        $previousUrlElem = '';
        $map = [];
        foreach ($explodedMatchedRoute as $k => $routeParam) {
            $urlElem = $explodedUrl[$k] ?? null;
            if ($urlElem !== $routeParam) {
                if ($routeParam === '*') {
                    $routeParam = $this->_lastEntityIdName($previousUrlElem);
                }
                $map[$routeParam] = $urlElem;
            } else {
                if ($urlElem) {
                    $previousUrlElem = $urlElem;
                }
            }
        }
        return $map;
    }

    private function _lastEntityIdName(string $last): string
    {
        if ($last) {
            return '{' . Inflector::singularize($last) . 'ID}';
        }
        return self::ENTITY_BRACES;
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

    public static function acceptLanguage(): bool|string
    {
        $acceptLanguage = Configure::read('Swagger.acceptLanguage');
        if ($acceptLanguage === false) {
            return false;
        }
        if (!$acceptLanguage) {
            $acceptLanguage
                = 'ISO 639-1 2 letter language code (depending on setup: en, es, de, ar, eng, spa, es_AR, en_US)';
        }
        return $acceptLanguage;
    }

    private function _getHeaderParams(): array
    {
        $toRet = [];
        if (!$this->_isPublicController()) {
            $toRet[] = [
                'description' => 'Auth token',
                'in' => 'header',
                'name' => 'Authentication',
                'example' => 'Bearer ****************',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
        }
        $acceptLanguage = $this->acceptLanguage();
        if ($acceptLanguage !== false) {
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

    public function getComponentSchemas(): StandardSchemas
    {
        return $this->schemas;
    }

    public function getLocationHeader(): ?string
    {
        return $this->_response->getHeader('Location')[0] ?? null;
    }

    public function getResponseSchema(): ?array
    {
        $fullJson = $this->getJson();
        if ($fullJson === null) {
            return null;
        }
        return $this->schemas->getResponseSchemas($fullJson, $this->getDescription());
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
                $properties[$property] = $this->getProp($value, $property);
            }
            return [
                'type' => 'array',
                'description' => $this->getDescription(),
                'items' => TypeParser::getItems($post),
            ];
        } else {
            return $this->schemas->parseProperties($post, $this->getDescription());
            //foreach ($post as $property => $value) {
            //    $properties[$property] = $this->getProp($value, $property);
            //}
            //return [
            //    'type' => 'object',
            //    'description' => $this->getDescription(),
            //    'properties' => $properties,
            //];
        }
    }

    public function getProp($value, string $property = null, int $depth = 0): array
    {
        return TypeParser::getProp($value, $property, $depth);
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
