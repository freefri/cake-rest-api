<?php

namespace RestApi\Controller\Traits;

use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use RestApi\Lib\Helpers\PaginationHelper;

/**
 * @property ServerRequest $request
 */
trait RestApiPaginationTrait
{
    private int $_defaultLimit = 10;

    protected function calculateOffset($page, $resultsPerPage = 10)
    {
        return PaginationHelper::calculateOffset($page, $resultsPerPage);
    }

    protected function getLinks(int $page, float $numPages, string $host): array
    {
        $paginator = new PaginationHelper($this->request);
        return $paginator->getLinks($page, $numPages, $host);
    }

    public function processQueryFilters(array $filters = null): array
    {
        return PaginationHelper::processQueryFiltersStatic($filters);
    }

    protected function getPage(): int
    {
        return (int)($this->request->getQueryParams()['page'] ?? 1);
    }

    protected function setDefaultLimit(int $defaultLimit): self
    {
        $this->_defaultLimit = $defaultLimit;
        return $this;
    }

    protected function getLimit(): int
    {
        return (int)($this->request->getQueryParams()['limit'] ?? $this->_defaultLimit);
    }

    protected function getPaginatedResult(Query $query, string $host): array
    {
        $limit = $this->getLimit();
        $page = $this->getPage();
        $offset = $this->calculateOffset($page, $limit);
        $pageResults = $query->limit($limit)->offset($offset)->toArray();
        $fetchedCount = count($pageResults);
        if ($fetchedCount < $limit) {
            $total = $offset + $fetchedCount;
        } else {
            $total = $query->count();
        }
        $numPages = ceil($total / $limit);
        return [
            'data' => $pageResults,
            'total' => $total,
            'limit' => $limit,
            '_links' => $this->getLinks($page, $numPages, $host)
        ];
    }
}
