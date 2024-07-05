<?php

declare(strict_types = 1);

namespace RestApi\Lib;

use Cake\Log\Engine\BaseLog;
use RestApi\Model\Table\LogEntriesTable;


class DatabaseLog extends BaseLog
{
    /** @var LogEntriesTable */
    public $LogEntries;

    public static function cls()
    {
        return 'Database';
    }

    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->LogEntries = LogEntriesTable::load();
    }

    public function log($level, $message, array $context = [])
    {
        try {
            return $this->LogEntries->saveLog($level, $message, $context);
        } catch (\Exception $e) {
            if (strpos($message, "\n") !== false) {
                $message = explode("\n", $message);
            }
            $err = [
                'msg' => 'Error saving logs',
                'level' => $level,
                'logError' => $e->getMessage(),
                'logTrace' => $e->getTraceAsString(),
                'originalError' => $message
            ];
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo(json_encode($err));
            exit;
        }
    }
}
