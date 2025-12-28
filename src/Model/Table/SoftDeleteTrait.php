<?php

namespace RestApi\Model\Table;

use Cake\Http\Exception\InternalErrorException;
use function Cake\I18n\__d as cake__d;

trait SoftDeleteTrait
{
    public function getSoftDeleteField(): ?string
    {
        try {
            return $this->_getSoftDeleteField();
        } catch (InternalErrorException $e) {
            return null;
        }
    }

    private function _getSoftDeleteField(): string
    {
        if (isset($this->softDeleteField)) {
            $field = $this->softDeleteField;
        } else {
            $field = 'deleted';
        }
        if ($this->getSchema()->getColumn($field) === null) {
            throw new InternalErrorException(
                cake__d('admin', 'Configured field `{0}` is missing from the table `{1}`.',
                    $field,
                    $this->getAlias()
                )
            );
        }
        return $field;
    }

    public function softDelete($primaryKey): void
    {
        $success = $this->softDeleteAll(['id' => $primaryKey]) > 0;
        if (!$success) {
            throw new InternalErrorException('Error soft deleting '. $primaryKey);
        }
    }

    public function softDeleteAll(array $condition): int
    {
        $query = $this->updateQuery();
        $statement = $query->update($this->getTable())
            ->set([$this->_getSoftDeleteField() => date('Y-m-d H:i:s')])
            ->where($condition);
        return $statement->execute()->rowCount();
    }
}
