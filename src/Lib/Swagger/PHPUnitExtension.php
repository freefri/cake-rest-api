<?php
declare(strict_types=1);

namespace RestApi\Lib\Swagger;

use PHPUnit\Runner\AfterLastTestHook;

class PHPUnitExtension implements AfterLastTestHook
{
    public function executeAfterLastTest(): void
    {
        $paths = $this->_readFiles();
        $counter = 1;
        foreach ($paths as &$path) {
            foreach ($path as &$method) {
                $method['operationId'] = '' . $counter;
                $counter++;
            }
        }
        $obj = [
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
        $this->_writeFile($obj);
    }

    private function _readFiles(): array
    {
        $dir = TMP.'tests'.DS.'swagger'.DS;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
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

    private function _writeFile(array $contents)
    {
        $dir = TMP.'tests'.DS;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = $dir . 'FULLswagger.json';
        $handle = fopen($filename, 'w') or die('cannot open the file to add swagger '.$filename);
        fwrite($handle, json_encode($contents, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
        fclose($handle);
        return $filename;
    }
}
