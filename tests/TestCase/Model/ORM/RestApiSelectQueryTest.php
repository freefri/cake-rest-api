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
            'initial_date1:gte' => '20-12-2023',
            'initial_date2:gt' => '20-12-2023',
            'initial_date3:lte' => '20-12-2023',
            'initial_date4:lt' => '20-12-2023',
        ];
        $query = $this->_getQuery();
        $query->handleTimeFilter($filters, 'initial_date1');
        $this->assertEquals('initial_date1 <= :c0', $this->_getSql($query));


        $query = $this->_getQuery();
        $query->handleTimeFilter($filters, 'initial_date2');
        $this->assertEquals('initial_date2 < :c0', $this->_getSql($query));


        $query = $this->_getQuery();
        $query->handleTimeFilter($filters, 'initial_date3');
        $this->assertEquals('initial_date3 >= :c0', $this->_getSql($query));


        $query = $this->_getQuery();
        $query->handleTimeFilter($filters, 'initial_date4');
        $this->assertEquals('initial_date4 > :c0', $this->_getSql($query));
    }

    private function _getSql(RestApiSelectQuery $query)
    {
        return $query->clause('where')->sql($query->getValueBinder());
    }
}
