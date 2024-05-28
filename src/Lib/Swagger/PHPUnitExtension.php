<?php
declare(strict_types=1);

namespace RestApi\Lib\Swagger;

use Cake\Core\Configure;
use PHPUnit\Runner\AfterLastTestHook;

class PHPUnitExtension implements AfterLastTestHook
{
    public function executeAfterLastTest(): void
    {
        $reader = new SwaggerReader();
        $paths = $reader->readFiles($this->getDirectory());
        $this->_writeFile($reader->getInfo($paths));
    }

    public static function getDirectory(): string
    {
        $dir = Configure::read('Swagger.jsonDir');
        if (!$dir) {
            $dir = TMP.'tests'.DS.'swagger'.DS;
        }
        return $dir;
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
