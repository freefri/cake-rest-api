<?php

namespace RestApi\Lib\Swagger;

use Cake\Controller\Controller;
use Cake\Http\Response;

class SwaggerFromController implements \JsonSerializable
{
    private array $_map = [];
    private string $_name;

    public function __construct(string $name)
    {
        $this->_name = $name;
    }

    public function addToSwagger(Controller $controller, array $request, Response $res)
    {
        $index = count($this->_map);
        $this->_map[$index] = new SwaggerTestCase($controller, $request, $res);
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

    public function getFirstTestCaseInRoute(string $route, string $method): SwaggerTestCase
    {
        $selectedRouteMethod = $this->buildMatrix()[$route][$method];
        $md5_elem = $selectedRouteMethod[array_key_first($selectedRouteMethod)];
        return $md5_elem[array_key_first($md5_elem)];
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

    private function _writeFile(string $name): string
    {
        $dir = TMP.'tests'.DS.'swagger'.DS;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = $dir . $name . '.json';
        $handle = fopen($filename, 'w') or die('cannot open the file to add swagger '.$filename);
        fwrite($handle, json_encode($this->_toArray(), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
        fclose($handle);
        return $filename;
    }

    public function writeFile()
    {
        return $this->_writeFile($this->_name);
    }
}
