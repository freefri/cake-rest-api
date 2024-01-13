<?php

namespace RestApi\Lib;

use Migrations\TestSuite\Migrator;

class RestMigrator extends Migrator
{

    public static function runAll(array $options = [])
    {
        $migrator = new Migrator();
        $migrator->runMany($options, false);
        $migrator->truncate('test', []);
    }
}
