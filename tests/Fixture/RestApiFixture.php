<?php

declare(strict_types = 1);

namespace RestApi\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use RestApi\Lib\RestPlugin;

class RestApiFixture extends TestFixture
{
    public function __construct()
    {
        $className = get_called_class();
        $namespace = explode('\\', $className)[0] ?? '';
        $tablePrefix = RestPlugin::getTablePrefixGeneric($namespace);

        $e = new \Exception('Custom trace exception');
        debug($e->getTraceAsString());
        if ($this->table === null) {
            $this->table = $tablePrefix . $this->_tableFromClass();
        }
        parent::__construct();
    }
}
