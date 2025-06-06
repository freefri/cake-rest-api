<?php

namespace RestApi\Lib\Swagger;

use RestApi\Model\Entity\RestApiEntity;

class StandardSchemas
{
    private array $schemas = [];

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function processStandardEntitySchema($json, ?array $parentJson, array $data): ?array
    {
        $entityType = $this->_getEntityType($json);
        if ($entityType) {
            if (isset($parentJson['total']) && isset($parentJson['limit']) && isset($parentJson['_links'])) {
                $resEntityType = $this->page($entityType);
                $this->schemas[$resEntityType] = [
                    'type' => 'object',
                    'properties' => [
                        'data' => [
                            '$ref' => '#/components/schemas/' . $entityType,
                        ],
                        'total' => TypeParser::getDataWithType($parentJson['total']),
                        'limit' => TypeParser::getDataWithType($parentJson['limit']),
                        '_links' => [
                            '$ref' => '#/components/schemas/PaginationLinks',
                        ],
                    ],
                ];
                $this->schemas['#/components/schemas/PaginationLinks'] = [
                    'type' => 'object',
                    'properties' => [
                        'self' => [
                            'type' => 'string',
                            'example' => $parentJson['_links']['self'] ?? '',
                        ],
                        'next' => [
                            'type' => 'string',
                            'example' => $parentJson['_links']['next'] ?? '',
                        ],
                        'prev' => [
                            'type' => 'string',
                            'example' => $parentJson['_links']['prev'] ?? '',
                        ],
                    ],
                ];
            } else {
                if (array_keys($parentJson) === ['data']) {
                    $resEntityType = $this->res($entityType);
                    $this->schemas[$resEntityType] = [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                '$ref' => '#/components/schemas/' . $entityType,
                            ]
                        ],
                    ];
                } else {
                    $resEntityType = null;
                }
            }
            if ($resEntityType) {
                unset($data['properties'][RestApiEntity::CLASS_NAME]);
                $this->schemas[$entityType] = $data;
                return [
                    '$ref' => '#/components/schemas/' . $entityType
                ];
            }
        }
        return null;
    }

    private function res(string $s): string
    {
        return 'Res' . $s;
    }

    private function page(string $s): string
    {
        return 'Page' . $s;
    }

    private function _getEntityType($json): ?string
    {
        $res = $json[RestApiEntity::CLASS_NAME] ?? null;
        if ($res) {
            $res = str_replace('\Model\Entity', '', $res);
            $res = str_replace('\\', 'Ns', $res);
        }
        return $res;
    }
}
