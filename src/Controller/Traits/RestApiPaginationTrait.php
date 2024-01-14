<?php

namespace RestApi\Controller\Traits;

use App\Lib\FullBaseUrl;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;

/**
 * @property ServerRequest $request
 */
trait PaginationTrait
{
    protected function calculateOffset($page, $resultsPerPage = 10)
    {
        return ($page - 1) * $resultsPerPage;
    }

    protected function _getHost(): string
    {
        return FullBaseUrl::host();
    }

    protected function getLinks(int $page, float $numPages): array
    {
        $links = [];
        $links['self']['href'] = $this->_getHost() . $this->_buildPageLink($page);
        if ($page < $numPages) {
            $links['next']['href'] = $this->_getHost() . $this->_buildPageLink($page + 1);
        }
        if ($page != 1) {
            $links['prev']['href'] = $this->_getHost() . $this->_buildPageLink($page - 1);
        }
        return $links;
    }

    public function processQueryFilters($filters): array
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
}
