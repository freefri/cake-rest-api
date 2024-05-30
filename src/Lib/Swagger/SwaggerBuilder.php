<?php

namespace RestApi\Lib\Swagger;

class SwaggerBuilder
{
    private SwaggerFromController $_data;
    private int $_operation = 1;

    public function __construct(SwaggerFromController $data)
    {
        $this->_data = $data;
    }

    public function toArray(): array
    {
        $toRet = [];
        foreach ($this->_data->buildMatrix() as $route => $method_code_md5_elem) {
            foreach ($method_code_md5_elem as $method => $code_md5_elem) {
                $elem = $this->_data->getFirstTestCaseInRoute($route, $method);
                $operation = [
                    'operationId' => $this->_operation++,
                    'summary' => '',
                    'description' => $elem->getDescription(),
                    'parameters' => $elem->getParams(),
                    'tags' => $elem->getTags(),
                    'responses' => [],
                ];
                $sec = $elem->getSecurity();
                if ($sec) {
                    $operation['security'] = $sec;
                }
                $requestSchema = $elem->getRequestSchema();
                if ($requestSchema) {
                    $operation['requestBody'] = [
                        'description' => '',
                        'content' => [
                            'application/json' => [
                                'schema' => $requestSchema
                            ]
                        ],
                    ];
                }
                foreach ($code_md5_elem as $md5_elem) {
                    foreach ($md5_elem as $case) {
                        $operation['responses'] = $this->_buildResponseSchema($case, $operation['responses']);
                    }
                    $toRet[$elem->getRoute()][$elem->getMethod()] = $operation;
                }
            }
        }
        return $toRet;
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
        }
        $json = 'application/json';
        $responseToAdd = $elem->getResponseSchema();
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
