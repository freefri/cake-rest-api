<?php

namespace RestApi\Model\Table;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use RestApi\Lib\Validator\RestApiValidator;
use RestApi\Lib\Validator\ValidationException;
use RestApi\Model\ORM\RestApiQuery;

abstract class RestApiTable extends Table
{
    use SoftDeleteTrait;

    const TABLE_PREFIX = '';

    protected $_validatorClass = RestApiValidator::class;

    protected function getTablePrefix(): string
    {
        return self::TABLE_PREFIX;
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

    public function query(): Query
    {
        return new RestApiQuery($this->getConnection(), $this);
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

    protected function getLastIncrementId($sellerId = null): int
    {
        $isGlobalIncrement = \App\Lib\Helpers\Configure::read('Platform.globalIncrement') ?? false;
        if ($sellerId === null && !$isGlobalIncrement) {
            throw new InternalErrorException('seller id must be provided');
        }
        $query = $this->find()
            ->where([
                'YEAR(created) = YEAR("' . date('Y-m-d') . '")',
                'increment !=' => 0
            ])
            ->order(['increment' => 'desc']);
        if (!$isGlobalIncrement) {
            $query->where(['seller_id' => $sellerId]);
        }
        $incr = $query->first();
        if (isset($incr->increment)) {
            return $incr->increment + 1;
        } else {
            return 1;
        }
    }
}
