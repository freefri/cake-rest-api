<?php

declare(strict_types = 1);

namespace RestApi\Model\Table;

use Cake\ORM\Behavior\TimestampBehavior;
use RestApi\Model\Entity\LogEntry;

class LogEntriesTable extends RestApiTable
{
    public function initialize(array $config): void
    {
        $this->addBehavior(TimestampBehavior::class);
    }

    protected function computeEnv()
    {
        $env = $_SERVER['APPLICATION_ENV'] ?? 'unknownEnv';
        if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
            $env = 'localhost';
        }
        //$env .= '_' . Configure::version();
        return $env;
    }

    public function saveLog($level, $message, $context)
    {
        list($title) = explode(':', $message);
        $titleLen = strlen($title);
        if ($titleLen > 30) {
            $title = null;
        }
        if (!$title) {
            list($title) = explode(']', $message);
            $namespaceSplit = namespaceSplit($title);
            if (count($namespaceSplit) == 2) {
                $title = $namespaceSplit[1];
            }
            $titleLen = strlen($title);
            if ($titleLen > 30) {
                $title = null;
            }
        }

        //$server['context'] = $context;
        $toSave = [
            'type' => $level,
            'title' => $title,
            'message' => $message,
            'environment' => $this->computeEnv(),
        ];

        /** @var LogEntry $entity */
        $entity = $this->newEntity($toSave);
        $entity->server = $this->_getServer();
        return $this->saveOrFail($entity);
    }

    private function _getServer()
    {
        $server['AUTH_TOKEN_UID'] = $_SERVER['AUTH_TOKEN_UID'] ?? '';
        $server['TAG_VERSION'] = $_SERVER['TAG_VERSION'] ?? '';
        $server['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? '';
        $server['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '';
        $server['APPLICATION_ENV'] = $_SERVER['APPLICATION_ENV'] ?? '';
        $server['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $server['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? '';
        $server['HTTP_ORIGIN'] = $_SERVER['HTTP_ORIGIN'] ?? '';
        $server['SERVER_ADDR'] = $_SERVER['SERVER_ADDR'] ?? '';
        $server['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $server['QUERY_STRING'] = $_SERVER['QUERY_STRING'] ?? '';
        $server['REQUEST_TIME_FLOAT'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? '';
        return json_encode($server);
    }
}
