<?php

declare(strict_types = 1);

namespace RestApi\Lib\Swagger\FileReader;

use RestApi\Lib\Swagger\TypeParser;

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

    private function _getMainCounterAmount(array $contents): int
    {
        $properties = array_keys($contents['properties']);
        if (count($properties) === 1) {
            $mainCounter = $properties[0];
        } else {
            $mainCounter = 'id';
        }
        return count($contents['properties'][$mainCounter] ?? []);
    }

    public function toArray(): array
    {
        $toRet = $this->merge();
        foreach ($toRet as &$contents) {
            $mainCounterAmount = $this->_getMainCounterAmount($contents);
            foreach ($contents['properties'] as $propertyName => &$contentArray) {
                $hasMainElement = $mainCounterAmount !== 0;
                $hasSameAmountAsMainElement = $mainCounterAmount === count($contentArray);
                if ($hasMainElement && $hasSameAmountAsMainElement) {
                    $hasNullable = false;
                    foreach ($contentArray as $c) {
                        if ($c['nullable'] ?? false) {
                            $hasNullable = true;
                        }
                    }
                    if (!$hasNullable) {
                        if (!isset($contents['required'][0]) || !in_array($propertyName, $contents['required'])) {
                            $contents['required'][] = $propertyName;
                        }
                    }
                }
                $contentArray = $this->getNewContent($contentArray, $propertyName);
            }
        }
        return $toRet;
    }

    public function getNewContent(mixed $contentArray, string $propertyName = ''): array
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
            if ($isValueNullable) {
                $newContent['nullable'] = $value['nullable'];
            }
            if (!isset($newContent['example']) && !isset($newContent['oneOf']) && isset($value['example'])) {
                $newContent['example'] = $value['example'];
            }
            // allow many types per property as oneOf
            if (isset($value['type'])) {
                if (isset($newContent['type'])) {
                    if ($value['type'] !== $newContent['type'] && !$this->_isStringNullable($value)) {
                        if (!$this->_isEmptyObjectOrArray($value)) {
                            // do not add any empty object or array if there is a type already existing
                            if ($this->_isEmptyObjectOrArray($newContent)) {
                                // replace empty object or array when it eas already there
                                $newContent = $value;
                            } else {
                                $newContent = ['oneOf' => [$newContent]];
                                $newContent['oneOf'][] = $value;
                            }
                        }
                    }
                } else {
                    if (isset($newContent['oneOf'][0]['type'])) {
                        $newContent['oneOf'] = $this->addOneOfType($newContent['oneOf'], $value);
                    }
                }
            } else if (isset($value['$ref'])) {
                $isNullable = $newContent['nullable'] ?? false;
                if (array_key_exists('example', $newContent)) {
                    $isNull = $newContent['example'] === null;
                } else {
                    $isNull = false;
                }
                if ($isNullable && $isNull) {
                    // if already existing is null add nullable schema
                    $newContent = $value;
                    $newContent['nullable'] = true;
                }
            }
        }
        return $newContent;
    }

    public function addOneOfType(array $types, array $newType): array
    {
        $shouldAdd = true;
        $toRet = [];
        foreach ($types as $oneOf) {
            if ($oneOf['type'] === $newType['type']) {
                $shouldAdd = false;
            }
            $toRet[] = $oneOf;
        }
        if ($shouldAdd) {
            $toRet[] = $newType;
        }
        return $toRet;
    }

    private function _isEmptyObjectOrArray(mixed $value): bool
    {
        $isObject = ($value['type'] ?? null) === 'object';
        $isUnknownObject = ($value['description'] ?? null) === TypeParser::ANYTHING;
        return $isObject && $isUnknownObject;
    }

    private function _isStringNullable(mixed $newContent): bool
    {
        return ($newContent['type'] ?? null) === 'string' && ($newContent['nullable'] ?? false);
    }
}
