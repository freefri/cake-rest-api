<?php

namespace RestApi\Controller\Traits;

use Cake\Http\ServerRequest;
use RestApi\Lib\Helpers\PaginationHelper;

/**
 * @property ServerRequest $request
 */
trait RestApiPaginationTrait
{
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
}
