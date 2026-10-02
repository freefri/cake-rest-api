<?php

declare(strict_types = 1);

namespace RestApi\Lib;

use Cake\Log\Engine\BaseLog;
use RestApi\Model\Table\LogEntriesTable;


class DatabaseLog extends BaseLog
{
    private const int MAX_MESSAGE_BYTES = 65535;

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
        $message = self::storableMessage((string)$message);
        try {
            $this->getLogTable()->saveLog($level, $message, $context);
        } catch (\Throwable $e) {
            error_log('DatabaseLog could not save a ' . $level . ' entry (' . $e->getMessage() . '): '
                . mb_strcut($message, 0, 2000, 'UTF-8'));
        }
    }

    /**
     * Strict-mode MySQL rejects invalid UTF-8 and anything longer than the TEXT column, so the message
     * is scrubbed and cut on a character boundary (a split character would be invalid UTF-8 again).
     */
    public static function storableMessage(string $message): string
    {
        $message = mb_scrub($message, 'UTF-8');
        if (strlen($message) <= self::MAX_MESSAGE_BYTES) {
            return $message;
        }
        $marker = "\n[cut: " . strlen($message) . ' bytes]';
        return mb_strcut($message, 0, self::MAX_MESSAGE_BYTES - strlen($marker), 'UTF-8') . $marker;
    }
}
