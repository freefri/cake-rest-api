<?php

namespace RestApi\Lib\Swagger;

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
        return [
            'openapi' => '3.0.0',
            'info' => [
                'version' => (date('Y') - 2017).'.'.date('W').'.'.date('dHi'),
                'title' => 'CT - OpenAPI 3.0',
                'description' => 'API Rest',
                'termsOfService' => 'https://github.com/freefri/cake-rest-api/',
                'contact' => [
                    'email' => 'freefri@freefri.es'
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

    public function readFiles(string $directory): array
    {
        $paths = $this->_readFiles($directory);
        $counter = 1;
        foreach ($paths as &$path) {
            foreach ($path as &$method) {
                $method['operationId'] = '' . $counter;
                $counter++;
            }
        }
        return $paths;
    }

    private function _readFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            if ($this->_createDirectoryIfNotExists) {
                mkdir($dir, 0777, true);
            } else {
                throw new NotFoundException('Swagger directory not found ' . $dir);
            }
        }
        $files = [];
        foreach (glob($dir . '*') as $fileName) {
            if (!str_contains($fileName, self::FULL_SWAGGER_JSON) && filesize($fileName) > 0) {
                $handle = fopen($fileName, 'r') or die('cannot open the file to add swagger '.$fileName);
                $contents = fread($handle, filesize($fileName));
                fclose($handle);
                $files = $files + json_decode($contents, true);
            }
        }
        return $files;
    }
}
