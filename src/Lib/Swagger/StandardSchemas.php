<?php

declare(strict_types = 1);

namespace RestApi\Lib\Swagger;

use RestApi\Model\Entity\RestApiEntity;

class StandardSchemas
{
    public const string PAGINATION_LINKS = 'PaginationLinks';
    private array $schemas = [];

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function getResponseSchemas(array $obj, string $testDescription = null): array
    {
        $entity = new StandardEntity($obj);
        $entityType = $entity->getInternalType();
        if ($entityType) {
            $entity = new StandardEntity($obj);
            if ($entity->isPaginationWrapper()) {
                $obj['_links'][RestApiEntity::CLASS_NAME] = self::PAGINATION_LINKS;
                $obj[RestApiEntity::CLASS_NAME] = $this->page($entityType);
            } else if ($entity->isDataResult()) {
                $obj[RestApiEntity::CLASS_NAME] = $this->res($entityType);
            }
            return $this->parseProperties($obj);
        }
        return TypeParser::getDataWithType($obj, $testDescription);
    }

    private function _addPropertyToSchema(StandardEntity $entity, string $property, array $parsedProperties): void
    {
        $entityType = $entity->type();
        if (!isset($this->schemas[$entityType])) {
            $this->schemas[$entityType] = TypeParser::object([], $entity->getDescription());
        }
        $this->schemas[$entityType]['properties'][$property] = $parsedProperties;
    }

    private function parseProperties(mixed $data): array
    {
        $entity = new StandardEntity($data);
        if ($entity->type()) {
            unset($data[RestApiEntity::CLASS_NAME]);
            foreach ($data as $property => $value) {
                $parsedProperties = $this->parseProperties($value);
                $this->_addPropertyToSchema($entity, $property, $parsedProperties);
            }
            return [
                '$ref' => '#/components/schemas/' . $entity->type(),
            ];
        } else {
            if (is_array($data) && isset($data[0])) {
                return [
                    'type' => 'array',
                    'items' => $this->parseProperties($data[0]),
                ];
            } else {
                return TypeParser::getDataWithType($data);
            }
        }
    }

    private function res(string $s): string
    {
        return 'Res' . $s;
    }

    private function page(string $s): string
    {
        return 'Paginated' . $s;
    }
}
