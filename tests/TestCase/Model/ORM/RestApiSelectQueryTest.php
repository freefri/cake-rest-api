<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Model\Table;

use Cake\Database\Connection;
use Cake\TestSuite\TestCase;
use RestApi\Model\ORM\RestApiSelectQuery;
use RestApi\Model\Table\OauthAccessTokensTable;

class RestApiSelectQueryTest extends TestCase
{
    private function _getQuery(): RestApiSelectQuery
    {
        $table = new OauthAccessTokensTable();
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $table->setConnection($connection);
        /** @var RestApiSelectQuery $query */
        $query = $table->find();
        return $query;
    }

    public function testHandleTimeFilter()
    {
        $filters = [
            'greaterThanEq:gte' => '20-12-2023',
            'greaterThan:gt' => '20-12-2023',
            'lowerThanEq:lte' => '20-12-2023',
            'lowerThan:lt' => '20-12-2023',
        ];
        $query = $this->_getQuery();
        $query->handleTimeFilter($filters, 'greaterThanEq');
        $this->assertEquals('greaterThanEq >= :c0', $this->_getSql($query));


        $query = $this->_getQuery();
        $query->handleTimeFilter($filters, 'greaterThan');
        $this->assertEquals('greaterThan > :c0', $this->_getSql($query));


        $query = $this->_getQuery();
        $query->handleTimeFilter($filters, 'lowerThanEq');
        $this->assertEquals('lowerThanEq <= :c0', $this->_getSql($query));


        $query = $this->_getQuery();
        $query->handleTimeFilter($filters, 'lowerThan');
        $this->assertEquals('lowerThan < :c0', $this->_getSql($query));
    }

    private function _getSql(RestApiSelectQuery $query)
    {
        return $query->clause('where')->sql($query->getValueBinder());
    }
}
