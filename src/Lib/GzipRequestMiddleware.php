<?php

declare(strict_types = 1);

namespace RestApi\Lib;

use Cake\Http\Exception\BadRequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Stream;

class GzipRequestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentEncoding = $request->getHeaderLine('Content-Encoding');

        if (stripos(strtolower($contentEncoding), 'gzip') !== false) {
            $body = $request->getBody();
            $body->rewind();
            $gzdata = $body->getContents();

            $decoded = gzdecode($gzdata);
            if ($decoded === false) {
                throw new BadRequestException('Invalid gzip payload in GzipRequestMiddleware');
            }

            $stream = new Stream('php://memory', 'rw');
            $stream->write($decoded);
            $stream->rewind();

            $request = $request
                ->withBody($stream)
                ->withoutHeader('Content-Encoding');
        }

        return $handler->handle($request);
    }
}
