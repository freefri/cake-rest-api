<?php

declare(strict_types = 1);

namespace RestApi\Lib\Swagger\FileReader;

interface FileReader
{
    public function add(array $contents): void;

    public function toArray(): array;
}
