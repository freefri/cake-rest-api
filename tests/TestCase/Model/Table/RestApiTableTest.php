<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Model\Table;

use Cake\ORM\Association;
use Cake\TestSuite\TestCase;
use RestApi\Model\Table\OauthAccessTokensTable;

class RestApiTableTest extends TestCase
{
    public function testHasMany_DefaultsToTheSelectStrategy()
    {
        $table = new OauthAccessTokensTable();

        $association = $table->hasMany('Comments');

        $this->assertSame(Association::STRATEGY_SELECT, $association->getStrategy());
    }

    public function testHasMany_KeepsAnExplicitStrategy()
    {
        $table = new OauthAccessTokensTable();

        $association = $table->hasMany('Comments', ['strategy' => Association::STRATEGY_SUBQUERY]);

        $this->assertSame(Association::STRATEGY_SUBQUERY, $association->getStrategy());
    }

    public function testBelongsToMany_DefaultsToTheSelectStrategy()
    {
        $table = new OauthAccessTokensTable();

        $association = $table->belongsToMany('Tags');

        $this->assertSame(Association::STRATEGY_SELECT, $association->getStrategy());
    }

    public function testBelongsToMany_KeepsAnExplicitStrategy()
    {
        $table = new OauthAccessTokensTable();

        $association = $table->belongsToMany('Tags', ['strategy' => Association::STRATEGY_SUBQUERY]);

        $this->assertSame(Association::STRATEGY_SUBQUERY, $association->getStrategy());
    }
}
