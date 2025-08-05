<?php

namespace RestApi\Lib\Swagger;

class TypeParser
{
    public const string ANYTHING = 'Any object';

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
                $data = self::_any(TypeParser::ANYTHING);
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
            $prop = [
                'type' => 'string',
                'example' => '' . self::anonymizeVariables($value, $property),
            ];
        }
        return $prop;
    }

    public static function anonymizeVariables($value, string $property = null)
    {
        if ($property) {
            $securedAnonymizedVariables = [
                'password',
                'access_token',
                'login_challenge',
                'client_assertion',
                'client_id',
                'vp_token',
                'Policy',
                'X-Amz-Credential',
                'X-Amz-Signature',
                'psp_redirect',
                'redirect_url',
                'trk_ga_ec',
                'signature',
                'created',
            ];
            $references = [
                'mandate_id',
                'merchant_reference',
                'reference',
                'filename',
            ];
            if ($value) {
                if (in_array($property, $references)) { // references
                    $value = preg_replace('/(?<=\d{2}-)\d{13}/', '*************', $value);
                }
                $regexDate = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/';
                if (preg_match($regexDate, $value)) {
                    return '2016-04-15T10:34:55+02:00';
                }
                $amz = 'X-Amz-Credential=';
                if (mb_strlen($value) > 200 && str_contains($value, $amz)) { // long amazon signed urls
                    return explode($amz, $value)[0] . $amz . '**********';
                }
            }
            if (in_array($property, $securedAnonymizedVariables)) { // secrets
                $value = preg_replace('/[a-zA-Z0-9]/', '*', $value);
            }
        }
        return $value;
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
