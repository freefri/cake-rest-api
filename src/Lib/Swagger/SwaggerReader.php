<?php

namespace RestApi\Lib\Swagger;

use Cake\Http\Exception\NotFoundException;

class SwaggerReader
{
    private bool $_createDirectoryIfNotExists;

    public function __construct(bool $createDirectoryIfNotExists = false)
    {
        $this->_createDirectoryIfNotExists = $createDirectoryIfNotExists;
    }

    public function getInfo(array $paths): array
    {
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
                ['url' => 'https://github.com/freefri/cake-rest-api/']
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
            if (is_array($path)) {
                foreach ($path as &$method) {
                    $method['operationId'] = '' . $counter;
                    $counter++;
                }
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
            if (filesize($fileName) > 0) {
                $handle = fopen($fileName, 'r') or die('cannot open the file to add swagger '.$fileName);
                $contents = fread($handle, filesize($fileName));
                fclose($handle);
                $files = $files + json_decode($contents, true);
            }
        }
        return $files;
    }
}
