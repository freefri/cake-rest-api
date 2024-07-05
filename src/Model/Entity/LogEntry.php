<?php

declare(strict_types = 1);

namespace RestApi\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property mixed|string $server
 */
class LogEntry extends Entity
{
    protected $_accessible = [
        '*' => false,
        'id' => false,
        'type' => true,
        'title' => true,
        'message' => true,
        'environment' => true
    ];

    protected $_hidden = ['server'];
}
