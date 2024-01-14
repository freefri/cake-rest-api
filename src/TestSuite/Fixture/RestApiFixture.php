<?php

namespace RestApi\TestSuite\Fixture;

use App\Model\Table\AppTable;
use Cake\TestSuite\Fixture\TestFixture;

class RestApiFixture extends TestFixture
{
    public function __construct()
    {
        if ($this->table === null) {
            $this->table = AppTable::TABLE_PREFIX . $this->_tableFromClass();
        }
        parent::__construct();
    }
}
