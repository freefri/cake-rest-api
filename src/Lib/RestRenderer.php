<?php
declare(strict_types=1);

namespace RestApi\Lib;

use Cake\Http\Response;

interface RestRenderer
{
    public function setHeadersForDownload(Response $response, $title = null): Response;

    public function render();
}
