<?php

declare(strict_types = 1);

namespace RestApi\Lib\Swagger;

use RestApi\Model\Entity\RestApiEntity;

class StandardEntity
{
    private mixed $data;

    public function __construct(mixed $data)
    {
        $this->data = $data;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getInternalType(): ?string
    {
        $data = $this->data;
        $entity = $data['data'][0] ?? null; // data with array of entities
        if (!$entity) {
            $entity = $data[0] ?? null; // directly array of entities
        }
        if (!$entity) {
            $entity = $data['data'] ?? null; // directly data with entity
        }
        if ($entity) {
            return $this->_parseType($entity);
        }
        return $this->_parseType($data); // directly entity

    }

    public function type(): ?string
    {
        return $this->_parseType($this->data);
    }

    private function _parseType(mixed $json): ?string
    {
        $res = $json[RestApiEntity::CLASS_NAME] ?? null;
        if ($res) {
            $res = str_replace('\Model\Entity', '', $res);
            $res = str_replace('\\', 'Ns', $res);
        }
        return $res;
    }

    public function getRequired(): array
    {
        if ($this->isPaginationWrapper()) {
            return $this->_paginationProps();
        } else if ($this->isDataResult()) {
            return $this->_dataResultProps();
        }
        return [];
    }

    public function getDescription(): string
    {
        if ($this->isPaginationWrapper()) {
            return 'Paginated ' . $this->getInternalType();
        } else if ($this->isDataResult()) {
            return 'Data wrapper for ' . $this->getInternalType();
        }
        $type = $this->type();
        if ($type === StandardSchemas::PAGINATION_LINKS) {
            return 'Pagination links.';
        }
        return 'Entity ' . $this->type();
    }

    private function _paginationProps(): array
    {
        return [
            'data',
            'total',
            'limit',
            '_links',
        ];
    }

    public function isPaginationWrapper(): bool
    {
        $props = $this->_paginationProps();
        foreach ($props as $prop) {
            if (!isset($this->data[$prop])) {
                return false;
            }
        }
        return true;
    }

    private function _dataResultProps(): array
    {
        return ['data'];
    }

    public function isDataResult(): bool
    {
        $props = $this->_dataResultProps();
        foreach ($props as $prop) {
            if (!isset($this->data[$prop])) {
                return false;
            }
        }
        return true;
    }
}
