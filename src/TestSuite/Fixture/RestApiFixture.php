<?php

declare(strict_types = 1);

namespace RestApi\TestSuite\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use RestApi\Lib\RestPlugin;

class RestApiFixture extends TestFixture
{
    public function __construct()
    {
        if ($this->table === null) {
            $className = get_called_class();
            $namespace = explode('\\', $className)[0] ?? '';
            $tablePrefix = RestPlugin::getTablePrefixGeneric($namespace);
            $this->table = $tablePrefix . $this->_tableFromClass();
        }
        parent::__construct();
    }
}
