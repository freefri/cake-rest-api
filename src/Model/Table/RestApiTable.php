<?php

namespace RestApi\Model\Table;

use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use RestApi\Lib\Validator\RestApiValidator;
use RestApi\Lib\Validator\ValidationException;
use RestApi\Model\ORM\RestApiQuery;
use RestApi\Model\ORM\RestApiSelectQuery;

abstract class RestApiTable extends Table
{
    use SoftDeleteTrait;

    const TABLE_PREFIX = '';

    protected $_validatorClass = RestApiValidator::class;

    protected function getTablePrefix(): string
    {
        return self::TABLE_PREFIX;
    }

    public static final function name(): string
    {
        $split = namespaceSplit(static::class);
        $name = array_pop($split);
        if (strpos($name, 'Table') === false) {
            throw new InternalErrorException('Class name must contain Table');
        }
        return substr($name, 0, -1 * strlen('Table'));
    }

    public static function nameWithPlugin()
    {
        $namespaceSplit = namespaceSplit(get_called_class());
        $classTableName = $namespaceSplit[1];
        $alias = substr($classTableName, 0, strlen($classTableName) - strlen('Table'));
        $plugin = explode('\\', $namespaceSplit[0])[0];
        if ($plugin !== Configure::read('App.namespace')) {
            return $plugin . '.' . $alias;
        }
        return $alias;
    }

    public static function load()
    {
        $alias = self::nameWithPlugin();
        /** @var self $table */
        $table = FactoryLocator::get('Table')->get($alias);
        return $table;
    }

    public static function addHasMany(RestApiTable $model): HasMany
    {
        return $model->hasMany(static::name(), ['className' => static::nameWithPlugin()]);
    }

    public static function addHasOne(RestApiTable $model): HasOne
    {
        return $model->hasOne(static::name(), ['className' => static::nameWithPlugin()]);
    }

    public static function addBelongsTo(RestApiTable $model): BelongsTo
    {
        return $model->belongsTo(static::name(), ['className' => static::nameWithPlugin()]);
    }

    public static function addBelongsToMany(RestApiTable $model): BelongsToMany
    {
        return $model->belongsToMany(static::name(), ['className' => static::nameWithPlugin()]);
    }

    public function addBehavior(string $name, array $options = [])
    {
        if (strpos($name, '\\') !== false) {
            $split = namespaceSplit($name);
            $name = array_pop($split);
            if (strpos($name, 'Behavior') === false) {
                throw new InternalErrorException('Class name must contain Behavior');
            }
            $name = substr($name, 0, -1 * strlen('Behavior'));
        }
        return parent::addBehavior($name, $options);
    }

    public function getTable(): string
    {
        if ($this->_table === null) {
            $table = namespaceSplit(static::class);
            $table = substr(end($table), 0, -5);
            if (!$table) {
                $table = $this->getAlias();
            }
            $this->_table = $this->getTablePrefix() . Inflector::underscore($table);
        }

        return $this->_table;
    }

    public function getFields(string $alias = null)
    {
        if (!$alias) {
            $alias = $this->_alias;
        }
        $fields = $this->getSchema()->columns();
        foreach ($fields as &$field) {
            $field = $alias . '.' . $field;
        }
        return $fields;
    }

    /*
    public function getVisibleColumns(Entity $entity)
    {
        $cols = $this->getSchema()->typeMap();
        $hidden = array_fill_keys($entity->getHidden(), true);

        $res = array_diff_key($cols, $hidden);

        foreach ($entity->getVirtual() as $name) {
            $value = $this->_getVirtualType($entity, $name);
            $res[$name] = $value;
        }
        return $res;
    }

    private function _getVirtualType($entity, $fieldName)
    {
        $value = $entity[$fieldName];
        return gettype($value);
    }
    */

    /**
     * @return Query
     * @deprecated As of 4.5.0 using query() is deprecated. Instead use `insertQuery()`,
     * `deleteQuery()`, `selectQuery()` or `updateQuery()`. The query objects
     * returned by these methods will emit deprecations that will become fatal errors in 5.0.
     * See https://book.cakephp.org/4/en/appendices/4-5-migration-guide.html for more information.
     */
    public function query(): Query
    {
        return new RestApiQuery($this->getConnection(), $this);
    }

    public function selectQuery(): SelectQuery
    {
        try {
            return new RestApiSelectQuery($this->getConnection(), $this);
        } catch (MissingConnectionException $e) {
            $this->_createMysqlFromConfig($e);
            throw $e;
        }
    }

    public function quotedSave(EntityInterface $entity, $options = [])
    {
        $driver = $this->getConnection()->getDriver();
        $oldState = $driver->isAutoQuotingEnabled();
        $driver->enableAutoQuoting();
        try {
            $res = $this->save($entity, $options);
        } finally {
            $driver->enableAutoQuoting($oldState);
        }
        return $res;
    }

    public function quotedSaveMany(array $entities, $options = [])
    {
        $driver = $this->getConnection()->getDriver();
        $oldState = $driver->isAutoQuotingEnabled();
        $driver->enableAutoQuoting();
        try {
            $res = $this->saveMany($entities, $options);
        } finally {
            $driver->enableAutoQuoting($oldState);
        }
        return $res;
    }

    public function patchEntity(EntityInterface $entity, array $data, array $options = []): EntityInterface
    {
        $res = parent::patchEntity($entity, $data, $options);
        if ($res->getErrors()) {
            throw new ValidationException($entity);
        }
        return $res;
    }

    public function __call($method, $args)
    {
        try {
            return parent::__call($method, $args);
        } catch (MissingConnectionException $e) {
            $this->_createMysqlFromConfig($e);
            throw $e;
        }
    }

    private function _createMysqlFromConfig($e): void
    {
        $dbConfig = $this->getConnection()->config();
        $database = $dbConfig['database'];
        $msg = "Connection to Mysql could not be established: SQLSTATE[HY000] [1049] Unknown database '$database'";
        if (strlen($database) > 3 && $dbConfig['driver'] == Mysql::class && $e->getMessage() == $msg) {
            $sql = "CREATE DATABASE $database";
            $dbConfig['database'] = null;
            $dbConfig['init'][] = $sql;
            $mysql = new Mysql($dbConfig);
            $mysql->connect();
        }
    }
}
