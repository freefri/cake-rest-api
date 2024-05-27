<?php
declare(strict_types=1);

namespace RestApi\Lib\Swagger;

use Cake\Core\Configure;
use PHPUnit\Runner\AfterLastTestHook;

class PHPUnitExtension implements AfterLastTestHook
{
    protected function getInfo(array $paths): array {
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
        $this->_writeFile($this->getInfo($paths));
    }

    public static function getDirectory(): string
    {
        $dir = Configure::read('Swagger.jsonDir');
        if (!$dir) {
            $dir = TMP.'tests'.DS.'swagger'.DS;
        }
        return $dir;
    }

    private function _readFiles(): array
    {
        $dir = $this->getDirectory();
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

    private function _writeFile(array $contents): ?string
    {
        $dir = Configure::read('Swagger.fullFileDir');
        if ($dir === false) {
            return null;
        }
        if (!$dir) {
            $dir = $this->getDirectory();
        }
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
