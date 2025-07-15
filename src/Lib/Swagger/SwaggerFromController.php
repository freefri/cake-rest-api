<?php

namespace RestApi\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Http\Response;

class SwaggerFromController implements \JsonSerializable
{
    public const string SCHEMA_DIR = 'component-schemas';
    private array $_map = [];

    public function addToSwagger(Controller $controller, array $request, Response $res)
    {
        $index = count($this->_map);
        $lastRoute = $this->getLastRoute($index);
        $this->_map[$index] = new SwaggerTestCase($controller, $request, $res, $lastRoute);
    }

    private function getLastRoute(int $index): ?string
    {
        /** @var SwaggerTestCase $lastTest */
        $lastTest = ($this->_map[$index - 1] ?? null);
        if (!$lastTest) {
            return null;
        }
        return $lastTest->getRoute();
    }

    public function buildMatrix(): array
    {
        $toRet = [];
        /** @var SwaggerTestCase $obj */
        foreach ($this->_map as $obj) {
            $method = $obj->getMethod();
            $resCode = $obj->getStatusCodeString();
            $md5toAvoidDuplicates = $obj->toMd5();
            $toRet[$obj->getRoute()][$method][$resCode][$md5toAvoidDuplicates] = $obj;
        }
        return $this->_removeMethodsWithoutOk($toRet);
    }

    private function _toArray(): array
    {
        $res = $this->buildMatrix();
        $builder = new SwaggerBuilder($this);
        return $builder->toArray();
    }

    private function _removeMethodsWithoutOk(array $toRet): array
    {
        foreach ($toRet as $route => $one) {
            foreach ($one as $method => $two) {
                $countOk = 0;
                foreach ($two as $code => $obj) {
                    $isOk = $code >= 200 && $code <= 399;
                    if ($isOk) {
                        $countOk++;
                    }
                }
                if (!$countOk) {
                    unset($toRet[$route][$method]);
                }
            }
        }
        return $toRet;
    }

    public function jsonSerialize(): array
    {
        return $this->_toArray();
    }

    private function _writeSingleTestJsonFile(string $name, array $content): string
    {
        $dir = PHPUnitExtension::getDirectory();
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = $dir . $name . '.json';
        $handle = fopen($filename, 'w') or die('cannot open the file to add swagger '.$filename);
        fwrite($handle, json_encode($content, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
        fclose($handle);
        return $filename;
    }

    public function writeFiles(string $name): void
    {
        $toArray = $this->_toArray();
        $this->_writeSingleTestJsonFile($name, $toArray[SwaggerBuilder::PATHS]);
        $this->_writeSingleTestJsonFile(self::SCHEMA_DIR . DS . $name, $toArray[SwaggerBuilder::SCHEMAS]);
    }
}
