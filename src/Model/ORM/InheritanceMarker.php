<?php

namespace RestApi\Model\ORM;

use Cake\Http\Exception\InternalErrorException;

class InheritanceMarker
{
    const DEFAULT = '__--defaultMarkerValue--__';

    protected $_markerField;
    protected $_map;

    public function setMarkerField(string $markerField) : void
    {
        $this->_markerField = $markerField;
    }

    public function getMarkerField() : string
    {
        return $this->_markerField;
    }

    public function addDefaultInheritedEntity(string $entityClass) : void
    {
        $this->_map[self::DEFAULT] = $entityClass;
    }

    public function addInheritedEntity($markerValue, string $entityClass) : void
    {
        $this->_map[$markerValue] = $entityClass;
    }

    public function getClassByType($markerValue) : string
    {
        if (!isset($this->_map[self::DEFAULT])) {
            throw new InternalErrorException('Invalid default marker value for '.$this->_markerField);
        }
        if (!isset($this->_map[$markerValue])) {
            return $this->_map[self::DEFAULT];
        }
        return $this->_map[$markerValue];
    }
}
