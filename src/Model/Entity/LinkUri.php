<?php

declare(strict_types = 1);

namespace RestApi\Model\Entity;

class LinkUri extends RestApiEntity
{
    public static function onlySelf(string $self): array
    {
        $links = [
            'self' => new LinkHref(['href' => $self])
        ];
        $uris = new LinkUri($links);
        return $uris->toJsonArray();
    }
}
