<?php
declare(strict_types=1);

namespace RestApi\Lib\Swagger;

use Cake\Core\Configure;
use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\FinishedSubscriber;
use RestApi\Lib\RestPlugin;
use RestApi\Lib\Swagger\FileReader\PathReader;
use RestApi\Lib\Swagger\FileReader\SwaggerReader;

class PHPUnitExtension implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        $reader = new SwaggerReader(true);
        // not implemented
        $paths = $reader->readFiles($this->getDirectory());
        $paths = $reader->readFiles($this->getDirectory(), new PathReader());
        //$schemas = $reader->readFiles($this->getDirectory(), new SchemaReader());
        $this->_writeFile($reader->getInfo($paths));
    }

    public static function getDirectory(): string
    {
        $dir = Configure::read('Swagger.jsonDir');// default directory to store json files
        if (!$dir) {
            $dir = ROOT . DS . RestPlugin::swaggerPath() . DS;
        }
        return $dir;
    }

    private function _writeFile(array $contents): ?string
    {
        $dir = Configure::read('Swagger.fullFileDir');// when false or null do not generate full json
        if (!$dir) {
            return null;
        }
        if ($dir === true) {// when true generate with default directory (optionally add string as directory path)
            $dir = $this->getDirectory();
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = $dir . SwaggerReader::FULL_SWAGGER_JSON;
        $handle = fopen($filename, 'w') or die('cannot open the file to add swagger '.$filename);
        fwrite($handle, json_encode($contents, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
        fclose($handle);
        return $filename;
    }
}
