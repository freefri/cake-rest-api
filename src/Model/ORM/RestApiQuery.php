<?php

namespace RestApi\Model\ORM;

use Cake\Database\StatementInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use RestApi\Lib\Exception\SilentException;

/**
 * @deprecated As of 4.5.0 using query() is deprecated. Instead use `insertQuery()`,
 * `deleteQuery()`, `selectQuery()` or `updateQuery()`. The query objects
 * returned by these methods will emit deprecations that will become fatal errors in 5.0.
 * See https://book.cakephp.org/4/en/appendices/4-5-migration-guide.html for more information.
 */
class RestApiQuery extends Query
{
    const WITH_DELETED = 'with_deleted';

    /**
     * @deprecated As of 4.5.0 using query() is deprecated. Instead use `insertQuery()`,
     * `deleteQuery()`, `selectQuery()` or `updateQuery()`. The query objects
     * returned by these methods will emit deprecations that will become fatal errors in 5.0.
     * See https://book.cakephp.org/4/en/appendices/4-5-migration-guide.html for more information.
     */
    public function withDeleted(bool $includeDeleted): Query
    {
        $containOptions = $includeDeleted ? [self::WITH_DELETED] : [];
        $this->applyOptions($containOptions);
        return $this;
    }

    /**
     * @deprecated As of 4.5.0 using query() is deprecated. Instead use `insertQuery()`,
     * `deleteQuery()`, `selectQuery()` or `updateQuery()`. The query objects
     * returned by these methods will emit deprecations that will become fatal errors in 5.0.
     * See https://book.cakephp.org/4/en/appendices/4-5-migration-guide.html for more information.
     */
    public function triggerBeforeFind(): void
    {
        if (!$this->_beforeFindFired && $this->_type === 'select') {
            parent::triggerBeforeFind();
            $repository = $this->getRepository();
            $options = $this->getOptions();
            if (!is_array($options) || !in_array(self::WITH_DELETED, $options)) {
                /** @var \RestApi\Model\Table\RestApiTable $repository */
                $fieldName = $repository->getSoftDeleteField();
                if ($fieldName) {
                    $aliasedField = $repository->aliasField($fieldName);
                    $this->andWhere($aliasedField . ' IS NULL');
                }
            }
        }
    }

    /**
     * @deprecated As of 4.5.0 using query() is deprecated. Instead use `insertQuery()`,
     * `deleteQuery()`, `selectQuery()` or `updateQuery()`. The query objects
     * returned by these methods will emit deprecations that will become fatal errors in 5.0.
     * See https://book.cakephp.org/4/en/appendices/4-5-migration-guide.html for more information.
     */
    protected function _execute(): ResultSetInterface
    {
        $this->triggerBeforeFind();
        if ($this->_results) {
            $decorator = $this->_decoratorClass();

            /** @var \Cake\Datasource\ResultSetInterface */
            return new $decorator($this->_results);
        }

        $statement = $this->getEagerLoader()->loadExternal($this, $this->execute());

        return new RestApiResultSet($this, $statement);
    }

    /**
     * @deprecated As of 4.5.0 using query() is deprecated. Instead use `insertQuery()`,
     * `deleteQuery()`, `selectQuery()` or `updateQuery()`. The query objects
     * returned by these methods will emit deprecations that will become fatal errors in 5.0.
     * See https://book.cakephp.org/4/en/appendices/4-5-migration-guide.html for more information.
     */
    public function firstOrSilent(string $message)
    {
        $res = $this->first();
        if (!$res) {
            throw new SilentException($message, 404);
        }
        return $res;
    }

    /**
     * @deprecated As of 4.5.0 using query() is deprecated. Instead use `insertQuery()`,
     * `deleteQuery()`, `selectQuery()` or `updateQuery()`. The query objects
     * returned by these methods will emit deprecations that will become fatal errors in 5.0.
     * See https://book.cakephp.org/4/en/appendices/4-5-migration-guide.html for more information.
     */
    public function execute(): StatementInterface
    {
        try {
            $statement = $this->_connection->run($this);
        } catch (\InvalidArgumentException $e) {
            $search = 'Cannot convert value of type';
            if (substr($e->getMessage(), 0, strlen($search)) === $search) {
                //debug($this->sql());
                //debug($this->getValueBinder()->bindings());
                debug($this->__debugInfo());
            }
            throw $e;
        }
        $this->_iterator = $this->_decorateStatement($statement);
        $this->_dirty = false;

        return $this->_iterator;
    }

    /**
     * @deprecated As of 4.5.0 using query() is deprecated. Instead use `insertQuery()`,
     * `deleteQuery()`, `selectQuery()` or `updateQuery()`. The query objects
     * returned by these methods will emit deprecations that will become fatal errors in 5.0.
     * See https://book.cakephp.org/4/en/appendices/4-5-migration-guide.html for more information.
     */
    public function __debugInfo(): array
    {
        $res = parent::__debugInfo();
        $replaced = $res['sql'];
        foreach ($res['params'] ?? [] as $name => $param) {
            if (isset($param['value'])) {
                $value = $param['value'];
                if (!is_numeric($value)) {
                    if ($value instanceof FrozenTime) {
                        $value = '"' . $value->toIso8601String() . '"';
                    } else  {
                        $value = '"' . $value . '"';
                    }
                }
            } else {
                $value = 'null';
            }
            $replaced = preg_replace('/' . $name . '/', $value, $replaced, 1);
        }
        $toRet = [
            '(help)' => $res['(help)'],
            'sql' => $res['sql'],
            'sql_with_params' => $replaced,
        ];
        foreach ($res as $key => $value) {
            $toRet[$key] = $value;
        }
        return $toRet;
    }
}
