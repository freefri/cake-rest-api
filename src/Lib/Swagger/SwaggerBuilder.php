<?php

namespace RestApi\Lib\Swagger;

use Cake\Http\Exception\InternalErrorException;

class SwaggerBuilder
{
    public const string SCHEMAS = 'componentSchemas';
    public const string PATHS = 'paths';

    private SwaggerFromController $_data;
    private int $_operation = 1;
    /** @var StandardSchemas[] */
    private array $componentSchemas = [];

    public function __construct(SwaggerFromController $data)
    {
        $this->_data = $data;
    }

    public function _getFirstTestCaseInRoute(array $selectedRouteMethod): SwaggerTestCase
    {
        $md5_elem = $selectedRouteMethod[array_key_first($selectedRouteMethod)];
        return $md5_elem[array_key_first($md5_elem)];
    }

    public function toArray(): array
    {
        $toRet = [];
        foreach ($this->_data->buildMatrix() as $route => $method_code_md5_elem) {
            foreach ($method_code_md5_elem as $method => $code_md5_elem) {
                $tags = $this->_getFirstNoErrorTags($code_md5_elem);
                if (substr($route, -1) === '/') {
                    $list = 'List';
                } else {
                    $list = '';
                }
                $firstTestCase = $this->_getFirstTestCaseInRoute($code_md5_elem);
                $operation = [
                    'operationId' => $method . $list . $tags[0],
                    'summary' => '',
                    'description' => $firstTestCase->getDescription(),
                    'parameters' => $this->_getParamsInRoute($code_md5_elem),
                    'tags' => $tags,
                    'responses' => [],
                ];
                $sec = $firstTestCase->getSecurity();
                if ($sec) {
                    $operation['security'] = $sec;
                }
                $requestBody = $this->_getRequestBodyInRoute($code_md5_elem);
                if ($requestBody) {
                    $operation['requestBody'] = $requestBody;
                }
                foreach ($code_md5_elem as $md5_elem) {
                    foreach ($md5_elem as $testCase) {
                        $operation['responses'] = $this->_buildResponseSchema($testCase, $operation['responses']);
                    }
                    $toRet[$firstTestCase->getRoute()][$firstTestCase->getMethod()] = $operation;
                }
            }
        }
        return [
            SwaggerBuilder::PATHS => $toRet,
            SwaggerBuilder::SCHEMAS => $this->getComponentSchemas(),
        ];
    }

    public function getComponentSchemas(): array
    {
        $toRet = [];
        foreach ($this->componentSchemas as $componentSchema) {
            $schemas = $componentSchema->getSchemas();
            foreach ($schemas as $entityName => $entityDescription) {
                foreach ($entityDescription as $key => $values) {
                    if ($key === 'properties') {
                        foreach ($values as $propertyName => $propertyDescription) {
                            if (!isset($toRet[$entityName][$key][$propertyName])) {
                                $toRet[$entityName][$key][$propertyName] = $propertyDescription;
                            } else {
                                $toRet[$entityName][$key][$propertyName] = array_merge(
                                    $toRet[$entityName][$key][$propertyName],
                                    $propertyDescription
                                );
                            }
                        }
                    } else {
                        $toRet[$entityName][$key] = $values;
                    }
                }
            }
        }
        return $toRet;
    }

    public function addSchemas(StandardSchemas $s): void
    {
        $this->componentSchemas[] = $s;
    }

    public function _getFirstNoErrorTags(array $selectedRouteMethod): array
    {
        foreach ($selectedRouteMethod as $md5_elem) {
            /** @var SwaggerTestCase $testCase */
            foreach ($md5_elem as $testCase) {
                $tags = $testCase->getTags();
                if ($tags != ['Error']) {
                    return $tags;
                }
            }
        }
        throw new InternalErrorException('Object structure is not valid ' . json_encode($selectedRouteMethod));
    }

    public function _getRequestBodyInRoute(array $selectedRouteMethod): array
    {
        $requestSchema = [];
        foreach ($selectedRouteMethod as $md5_elem) {
            /** @var SwaggerTestCase $testCase */
            foreach ($md5_elem as $testCase) {
                $req = $testCase->getRequestSchema();
                if ($req && $testCase->getStatusCode() < 400) {
                    $requestSchema[] = $req;
                }
            }
        }
        if (!$requestSchema) {
            return [];
        }
        $description = '';
        $count = count($requestSchema);
        if ($count === 1) {
            $requestSchema = $requestSchema[0];
        } else {
            $description = "Request body can match to any of the $count provided schemas";
            $requestSchema = [
                'oneOf' => $requestSchema
            ];
        }
        return [
            'description' => $description,
            'content' => [
                'application/json' => [
                    'schema' => $requestSchema
                ]
            ],
        ];
    }

    public function _getParamsInRoute(array $selectedRouteMethod): array
    {
        $toRet = [];
        foreach ($selectedRouteMethod as $md5_elem) {
            /** @var SwaggerTestCase $testCase */
            foreach ($md5_elem as $testCase) {
                foreach ($testCase->getParams() as $param) {
                    $key = $param['in'] . '__' . $param['name'];
                    if (!isset($toRet[$key])) { // add same in-name only once (do not duplicate parameters)
                        $toRet[$key] = $param;
                    }
                }
            }
        }
        return array_values($toRet);
    }

    private function _buildResponseSchema(SwaggerTestCase $elem, array $existingResponses): array
    {
        $code = $elem->getStatusCodeString();
        if ($code == 204) {
            $description = 'No content';
            if ($elem->getMethod() === 'delete') {
                $description .= '. Successfully deleted.';
            }
            $existingResponses[$code] = ['description' => $description];
            return $existingResponses;
        } else if ($code == 301 || $code == 302) {
            $description = 'Redirect. ' . $elem->getStatusDescription();
            $existingResponses[$code] = [
                'description' => $description,
                'headers' => [
                    'Location' => [
                        'description' => $elem->getDescription(),
                        'schema' => [
                            'type' => 'string',
                            'example' => $elem->getLocationHeader(),
                        ],
                    ]
                ]
            ];
            return $existingResponses;
        }
        $json = 'application/json';
        $responseToAdd = $elem->getResponseSchema();
        $this->addSchemas($elem->getComponentSchemas());
        if ($responseToAdd === null) {
            return $existingResponses;
        }
        if (!isset($existingResponses[$code]['content'])) {
            $existingResponses[$code] = [
                'description' => $elem->getStatusDescription(),
                'content' => [
                    $json => [
                        'schema' => $responseToAdd
                    ]
                ],
            ];
            return $existingResponses;
        }
        if (isset($existingResponses[$code]['content'][$json]['schema']['oneOf'][0])) {
            foreach ($existingResponses[$code]['content'][$json]['schema'] as $existingResponse) {
                $isSameContent = md5(json_encode($existingResponse)) === md5(json_encode($responseToAdd));
                $isSameKeys = array_keys($existingResponse) === array_keys($responseToAdd);
                if ($isSameKeys || $isSameContent) {
                    return $existingResponses;
                }
            }
            $existingResponses[$code]['content'][$json]['schema']['oneOf'][] = $responseToAdd;
            return $existingResponses;
        } else {
            $currentResponse = $existingResponses[$code]['content'][$json]['schema'];
            $isSameContent = md5(json_encode($currentResponse)) === md5(json_encode($responseToAdd));
            $isSameKeys = array_keys($currentResponse) === array_keys($responseToAdd);
            if ($isSameKeys || $isSameContent) {
                return $existingResponses;
            }
            $existingResponses[$code]['content'][$json]['schema'] = [
                'oneOf' => [
                    $existingResponses[$code]['content'][$json]['schema'],
                    $responseToAdd
                ]
            ];
            return $existingResponses;
        }
    }
}
