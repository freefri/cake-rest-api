<?php

declare(strict_types = 1);

namespace RestApi\Lib\Swagger\FileReader;

class SchemaReader implements FileReader
{
    private array $rawFiles = [];
    private array $schemasToRet;

    public function add(array $contents): void
    {
        $this->rawFiles[] = $contents;
    }

    public function merge(): array
    {
        $this->schemasToRet = [];
        foreach ($this->rawFiles as $file) {
            foreach ($file as $entityName => $content) {
                $this->_addEntityContents($entityName, $content);
            }
        }
        return $this->schemasToRet;
    }

    private function _addEntityContents(string $entityName, array $entity): void
    {
        if (!isset($this->schemasToRet[$entityName])) {
            $this->schemasToRet[$entityName] = $entity;
        } else {
            foreach ($entity as $key => $value) {
                if ($key === 'properties') {
                    foreach ($value as $propertyName => $propertyValue) {
                        $prop = $this->schemasToRet[$entityName]['properties'][$propertyName] ?? [];
                        $this->schemasToRet[$entityName]['properties'][$propertyName] = array_merge(
                            $prop,
                            $propertyValue
                        );
                    }
                }
            }
        }
    }

    public function toArray(): array
    {
        $toRet = $this->merge();
        foreach ($toRet as &$contents) {
            $idAmount = count($contents['properties']['id'] ?? []);
            foreach ($contents['properties'] as $propertyName => &$contentArray) {
                if ($idAmount !== 0 && $idAmount === count($contentArray)) {
                    $hasNullable = false;
                    foreach ($contentArray as $c) {
                        if ($c['nullable'] ?? false) {
                            $hasNullable = true;
                        }
                    }
                    if (!$hasNullable) {
                        $contents['required'][] = $propertyName;
                    }
                }
                $contentArray = $this->_getNewContent($contentArray, $propertyName);
            }
        }
        return $toRet;
    }

    private function _getNewContent(mixed $contentArray, string $propertyName = ''): array
    {
        $newContent = [];
        foreach ($contentArray as $value) {
            if (!$newContent) {
                $newContent = $value;
            }
            $isValueNullable = (bool)($value['nullable'] ?? false);
            if ($this->_isStringNullable($newContent)) {
                if (!$isValueNullable && isset($value['type'])) {
                    $newContent = $value;
                    $newContent['nullable'] = true;
                }
            }
            if (($newContent['type'] ?? null) !== 'array' && ($value['type'] ?? null) === 'array') {
                return $value;
            }
            if ($isValueNullable) {
                $newContent['nullable'] = $value['nullable'];
            }
            if (!isset($newContent['example']) && isset($value['example'])) {
                $newContent['example'] = $value['example'];
            }
            // allow many types per property as oneOf
            if (isset($value['type'])) {
                if (isset($newContent['type'])) {
                    if ($value['type'] !== $newContent['type'] && !$this->_isStringNullable($value)) {
                        $newContent['oneOf'][]['type'] = $newContent['type'];
                        $newContent['oneOf'][]['type'] = $value['type'];
                        unset($newContent['type']);
                    }
                } else {
                    if (isset($newContent['oneOf'][0]['type'])) {
                        $newContent['oneOf'] = $this->addOneOfType($newContent['oneOf'], $value['type']);
                    }
                }
            }
        }
        return $newContent;
    }

    public function addOneOfType(array $types, mixed $newType): array
    {
        $shouldAdd = true;
        $toRet = [];
        foreach ($types as $oneOf) {
            if ($oneOf['type'] === $newType) {
                $shouldAdd = false;
            }
            $toRet[] = $oneOf;
        }
        if ($shouldAdd) {
            $toRet[] = ['type' => $newType];
        }
        return $toRet;
    }

    private function _isStringNullable(mixed $newContent): bool
    {
        return ($newContent['type'] ?? null) === 'string' && ($newContent['nullable'] ?? false);
    }
}
