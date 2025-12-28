<?php

declare(strict_types = 1);

namespace RestApi\Model\Entity;

use Cake\Http\Exception\InternalErrorException;

class LinkHref extends RestApiEntity
{
    public function __construct(array $properties = [], array $options = [])
    {
        if (array_keys($properties) !== ['href']) {
            throw new InternalErrorException('LinkHref must have one single href property');
        }
        parent::__construct($properties, $options);
    }
}
