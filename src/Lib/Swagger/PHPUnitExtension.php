<?php
declare(strict_types=1);

namespace RestApi\Lib\Swagger;

use Cake\Core\Configure;
use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\FinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RestApi\Lib\RestPlugin;
use RestApi\Lib\Swagger\FileReader\PathReader;
use RestApi\Lib\Swagger\FileReader\SchemaReader;
use RestApi\Lib\Swagger\FileReader\SwaggerReader;

class PHPUnitExtension implements FinishedSubscriber, Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        if ($configuration->noOutput()) {
            return;
        }
        $facade->registerSubscriber(new PHPUnitExtension());
    }

    public function notify(Finished $event): void
    {
        $reader = new SwaggerReader(true);
        $schemasDirectory = $this->getDirectory() . SwaggerFromController::SCHEMA_DIR . DS;
        $componentSchemas = $reader->readFiles($schemasDirectory, new SchemaReader());
        $paths = $reader->readFiles($this->getDirectory(), new PathReader());
        $info = $reader->getInfo($paths);
        $info['components']['schemas'] = $componentSchemas;
        $this->_writeFile($info);
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
        if ($dir === null) {
            $relative = getenv('SWAGGER_RELATIVE_FILE_DIR');
            if ($relative) {
                $dir = ROOT . $relative;
            }
        }
        if (!$dir) {
            return null;
        }
        if ($dir === true || $dir === '1') {
            // when true generate with default directory (optionally add string as directory path)
            $dir = $this->getDirectory();
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $jsonFileName = Configure::read('Swagger.fullFileName');
        if (!$jsonFileName) {
            $jsonFileName = getenv('SWAGGER_FULL_FILE_NAME');
        }
        if (!$jsonFileName) {
            $jsonFileName = SwaggerReader::FULL_SWAGGER_JSON;
        }
        $filename = $dir . $jsonFileName;
        $handle = fopen($filename, 'w') or die('cannot open the file to add swagger '.$filename);
        fwrite($handle, json_encode($contents, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
        fclose($handle);
        return $filename;
    }
}
