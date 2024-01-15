<?php

namespace RestApi\Model\ORM;

use Cake\Database\StatementInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use RestApi\Lib\Exception\SilentException;

class RestApiSelectQuery extends SelectQuery
{
    const WITH_DELETED = 'with_deleted';

    public function withDeleted(bool $includeDeleted): Query
    {
        $containOptions = $includeDeleted ? [self::WITH_DELETED] : [];
        $this->applyOptions($containOptions);
        return $this;
    }

    public function triggerBeforeFind(): void
    {
        if (!$this->_beforeFindFired && $this->_type === 'select') {
            parent::triggerBeforeFind();
            $repository = $this->getRepository();
            $options = $this->getOptions();
            if (!is_array($options) || !in_array(self::WITH_DELETED, $options)) {
                /** @var \App\Model\Table\AppTable $repository */
                $fieldName = $repository->getSoftDeleteField();
                if ($fieldName) {
                    $aliasedField = $repository->aliasField($fieldName);
                    $this->andWhere($aliasedField . ' IS NULL');
                }
            }
        }
    }

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

    public function firstOrSilent(string $message)
    {
        $res = $this->first();
        if (!$res) {
            throw new SilentException($message, 404);
        }
        return $res;
    }

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
