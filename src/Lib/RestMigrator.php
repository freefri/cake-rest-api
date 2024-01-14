<?php

namespace RestApi\Lib;

use Cake\Http\Exception\HttpException;
use Migrations\Migrations;
use Migrations\TestSuite\Migrator;

class RestMigrator extends Migrator
{
    public static function runAll(array $options = [])
    {
        $migrator = new Migrator();
        $migrator->runMany($options, false);
        $migrator->truncate('test', []);
    }

    public static function runMigrations(array $migrationList, array $toRet): void
    {
        $migrations = new Migrations();
        try {
            foreach ($migrationList as $options) {
                $last = $options;
                //$migrations->markMigrated(20220113094521);
                $migrations->migrate($options);
                //$migrations->seed($options);
            }
        } catch (\Exception $e) {
            $toRet[] = $migrations->status($last);
            $toRet[] = $e->getMessage();
            throw new HttpException(json_encode($toRet), 500, $e);
        }
    }
}
