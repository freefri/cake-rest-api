<?php

declare(strict_types = 1);

namespace RestApi\Model\ORM;

use Cake\Collection\Collection;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\ResultSetFactory;
use SplFixedArray;

class RestApiResultSet extends ResultSetFactory
{
    public function createResultSet(iterable $results, ?SelectQuery $query = null): ResultSetInterface
    {
        if ($query) {
            $data = $this->collectData($query);

            if (is_array($results)) {
                foreach ($results as $i => $row) {
                    $results[$i] = $this->groupResult($row, $data);
                }

                $results = SplFixedArray::fromArray($results);
            } else {
                $results = (new Collection($results))
                    ->map(function ($row) use ($data) {
                        return $this->groupResult($row, $data);
                    });
            }
        }

        return new $this->resultSetClass($results);
    }
}
