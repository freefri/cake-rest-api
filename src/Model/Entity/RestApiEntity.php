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

    public function toChild(string $c, array $array)
    {
        if ($this->hasIdentifyEntities()) {
            $array[RestApiEntity::CLASS_NAME] = $c;
        }
        return $array;
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

    public function toJsonArray(): array
    {
        return json_decode(json_encode($this), true);
    }
}
