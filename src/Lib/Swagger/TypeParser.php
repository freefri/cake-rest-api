<?php

namespace RestApi\Lib\Swagger;

class TypeParser
{
    public static function getDataWithType($json, string $testDescription = null): array
    {
        $isArray = is_array($json) && isset($json[0]);
        if ($isArray) {
            $data = [
                'type' => 'array',
                'items' => self::getItems($json),
            ];
        } else {
            if ($json === null) {
                $data = [
                    'type' => 'string',
                    'nullable' => true,
                    'example' => $json,
                ];
            } else if (is_bool($json)) {
                $data = self::_boolean($json);
            } else if (is_numeric($json)) {
                $data = [
                    'type' => 'number',
                    'example' => $json + 0,
                ];
            } else if (is_string($json)) {
                $data = [
                    'type' => 'string',
                    'example' => $json,
                ];
            } else if ($json === []) {
                $data = self::_any('Any object'); // anything
            } else {
                $properties = [];
                foreach ($json as $property => $value) {
                    $properties[$property] = self::getProp($value, $property);
                }
                $description = $testDescription ? 'Generic object when: ' . $testDescription : 'Generic object.';
                $data = self::object($properties, $description);
            }
        }
        return $data;
    }

    public static function getProp($value, string $property = null, int $depth = 0): array
    {
        if (is_array($value)) {
            if ($value === []) {
                $prop = [
                    'type' => 'array',
                    'items' => self::_any('Empty array'),
                ];
            } else {
                $MAX_DEPTH = 10;
                if ($depth < $MAX_DEPTH) {
                    if (isset($value[0])) {
                        return [
                            'type' => 'array',
                            'items' => self::getItems($value, $depth),
                        ];
                    }
                    $properties = [];
                    foreach ($value as $property1 => $value1) {
                        $properties[$property1] = self::getProp($value1, $property1, $depth + 1);
                    }
                    $prop = self::object($properties, 'objectInArray');
                } else {
                    $example = str_replace('"', '`', json_encode($value, JSON_UNESCAPED_SLASHES));
                    $prop = [
                        'type' => 'string',
                        'example' => $example,
                    ];
                }
            }
        } else if (is_numeric($value)) {
            $prop = [
                'type' => 'number',
                'example' => $value + 0,
            ];
        } else if ($value === true || $value === false) {
            $prop = self::_boolean($value);
        } else {
            $securedAnonymizedVariables = [
                'password',
                'access_token',
                'login_challenge',
                'client_assertion',
                'client_id',
                'vp_token',
                'X-Amz-Signature',
            ];
            if ($property && in_array($property, $securedAnonymizedVariables)) {
                $value = str_repeat('*', mb_strlen($value));
            }
            $prop = [
                'type' => 'string',
                'example' => ''.$value,
            ];
        }
        return $prop;
    }

    public static function getItems($data, int $depth = 0): array
    {
        if (!is_array($data[0])) {
            return self::getProp($data[0], 0, $depth);
        }
        if (isset($data[0][0])) {
            return [
                'type' => 'array',
                'items' => self::getItems($data[0], $depth),
            ];
        }
        foreach ($data[0] as $property => $value) {
            $properties[$property] = self::getProp($value, $property, $depth);
        }
        return self::object($properties, 'getItems');
    }

    public static function object(array $properties, string $description = null): array
    {
        $res = [
            'type' => 'object',
            'properties' => $properties,
        ];
        if ($description !== null) {
            $res['description'] = $description;
        }
        return $res;
    }

    public static function _any(string $description = null): array
    {
        return [
            'type' => 'object',
            'description' => $description,
            'additionalProperties' => true,
        ];
    }

    private static function _boolean(bool $example)
    {
        return [
            'type' => 'boolean',
            'example' => $example,
        ];
    }
}
