<?php

declare(strict_types = 1);

namespace RestApi\Lib\Swagger\FileReader;

class PathReader implements FileReader
{
    private array $files = [];

    public function add(array $contents): void
    {
        $this->files = $this->files + $contents;
    }

    public function toArray(): array
    {
        return $this->files;
    }
}
