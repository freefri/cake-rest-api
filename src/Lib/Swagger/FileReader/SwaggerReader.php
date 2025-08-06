<?php

namespace RestApi\Lib\Swagger\FileReader;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;

class SwaggerReader
{
    public const FULL_SWAGGER_JSON = 'FULLswagger.json';

    private bool $_createDirectoryIfNotExists;

    public function __construct(bool $createDirectoryIfNotExists = false)
    {
        $this->_createDirectoryIfNotExists = $createDirectoryIfNotExists;
    }

    public function getInfo(array $paths): array
    {
        $serverUrl = ($_SERVER['HTTP_HOST'] ?? '');
        if ($serverUrl) {
            $serverUrl = 'https://' . $serverUrl;
        } else {
            $serverUrl = 'https://github.com/freefri/cake-rest-api/';
        }
        $version = Configure::read('Swagger.apiVersion');
        if (!$version) {
            $version = (date('Y') - 2017).'.'.date('W').'.'.date('dHi');
        }
        return [
            'openapi' => '3.0.0',
            'info' => [
                'version' => $version,
                'title' => 'CT - OpenAPI 3.0',
                'description' => 'API Rest',
                'termsOfService' => 'https://github.com/freefri/cake-rest-api/',
                'contact' => [
                    'email' => 'cake-rest-api@freefri.es'
                ],
            ],
            'servers' => [
                ['url' => $serverUrl]
            ],
            'tags' => [],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ]
                ],
            ],
        ];
    }

    public function readFiles(string $directory, FileReader $reader): array
    {
        return $this->_readFiles($directory, $reader);
    }

    private function _readFiles(string $dir, FileReader $reader): array
    {
        if (!is_dir($dir)) {
            if ($this->_createDirectoryIfNotExists) {
                mkdir($dir, 0777, true);
            } else {
                throw new NotFoundException('Swagger directory not found ' . $dir);
            }
        }
        foreach (glob($dir . '*') as $fileName) {
            $isSchemaDir = is_dir($fileName);
            $isFullSwaggerJsonFile = str_contains($fileName, self::FULL_SWAGGER_JSON);
            if (!$isSchemaDir && !$isFullSwaggerJsonFile && filesize($fileName) > 0) {
                $handle = fopen($fileName, 'r') or die('cannot open the file to add swagger '.$fileName);
                $contents = fread($handle, filesize($fileName));
                fclose($handle);
                $reader->add(json_decode($contents, true));
            }
        }
        return $reader->toArray();
    }
}
