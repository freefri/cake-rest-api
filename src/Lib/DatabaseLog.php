<?php

declare(strict_types = 1);

namespace RestApi\Lib;

use Cake\Log\Engine\BaseLog;
use RestApi\Model\Table\LogEntriesTable;


class DatabaseLog extends BaseLog
{
    public ?LogEntriesTable $LogEntries = null;

    public static function cls()
    {
        return 'Database';
    }

    public function __construct($options = [])
    {
        parent::__construct($options);
    }

    protected function getLogTable(): LogEntriesTable
    {
        if (!$this->LogEntries) {
            $this->LogEntries = LogEntriesTable::load();
        }
        return $this->LogEntries;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $table = $this->getLogTable();
        try {
            $table->saveLog($level, $message, $context);
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
