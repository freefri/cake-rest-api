<?php

namespace RestApi\Lib\Helpers;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;

class PaginationHelper
{
    public function __construct(ServerRequest $req)
    {
        $this->request = $req;
    }

    public function getPage(): int
    {
        return $this->request->getQueryParams()['page'] ?? 1;
    }

    public function getLimit(): int
    {
        return $this->request->getQueryParams()['limit'] ?? 10;
    }

    public function processQueryFilters(): array
    {
        $filters = $this->request->getQueryParams();
        return $this->processQueryFiltersStatic($filters);
    }

    public static function processQueryFiltersStatic(array $filters): array
    {

        if (isset($filters['limit']) && $filters['limit'] > 310) {
            throw new BadRequestException('Limit ' . $filters['limit'] . ' is too high');
        }
        foreach ($filters as $key => &$param) {
            if ($key == 'title') {
                continue;
            }
            if (strpos($param, ',') !== false) {
                $param = explode(',', $param);
            }
        }
        return $filters;
    }
    public static function calculateOffset($page, $resultsPerPage = 10)
    {
        return self::_calculateOfsset($page, $resultsPerPage);
    }

    private static function _calculateOfsset($page, $resultsPerPage = 10)
    {
        return ($page - 1) * $resultsPerPage;
    }

    public function processQuery(Query $query): Query
    {
        $offset = $this->_calculateOfsset($this->getPage(), $this->getLimit());
        return $query->limit($this->getLimit())->offset($offset);
    }

    private function _getLinks(int $total, string $host): array
    {
        $numPages = ceil($total / $this->getLimit());
        $page = $this->getPage();
        return $this->getLinks($page, $numPages, $host);
    }

    public function getLinks(int $page, float $numPages, string $host): array
    {
        $links = [];
        $links['self']['href'] = $host . $this->_buildPageLink($page);
        if ($page < $numPages) {
            $links['next']['href'] = $host . $this->_buildPageLink($page + 1);
        }
        if ($page != 1) {
            $links['prev']['href'] = $host . $this->_buildPageLink($page - 1);
        }
        return $links;
    }

    private function _buildPageLink($page): string
    {
        $uri = $this->request->getUri()->getPath() . '?';
        $params = $this->request->getQueryParams();
        foreach ($params as $key => $param) {
            if ($key != 'page' && strpos($uri, $key) === false) {
                $uri .= $key . '=' . urlencode($param) . '&';
            }
        }
        return $uri . "page=$page";
    }

    public function getReturnArray(Query $query, string $host): array
    {
        $total = $query->count();
        $ret = $this->processQuery($query);
        return [
            'data' => $ret,
            'total' => $total,
            'limit' => $this->getLimit(),
            '_links' => $this->_getLinks($total, $host)
        ];
    }
}
