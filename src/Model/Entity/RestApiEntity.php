<?php

declare(strict_types = 1);

namespace RestApi\Model\Entity;

use Cake\Core\Configure;
use Cake\ORM\Entity;

class RestApiEntity extends Entity
{
    public const CLASS_NAME = '_c';

    public function getClassNamespace(): string
    {
        return 'App\Model\Entity\\';
    }

    protected function _get_c(): string
    {
        return str_replace($this->getClassNamespace(), '', get_called_class());
    }

    public static function hasIdentifyEntities(): bool
    {
        return !!Configure::read('Swagger.identifyEntities');
    }

    public function jsonSerialize(): array
    {
        if ($this->hasIdentifyEntities()) {
            $this->setVirtual([self::CLASS_NAME], true);
        }
        return parent::jsonSerialize();
    }
}
