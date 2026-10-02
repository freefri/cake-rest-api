<?php

declare(strict_types = 1);

namespace RestApi\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Psr\Log\LogLevel;
use RestApi\Lib\DatabaseLog;
use RestApi\Model\Table\LogEntriesTable;

class DatabaseLogTest extends TestCase
{
    public function testLog()
    {
        $this->withoutErrorLog(fn () => (new DatabaseLog())->log(LogLevel::DEBUG, 'Testing log in DatabaseLogTest'));

        $this->expectOutputString('');
    }

    public function testLog_cutsMessageLongerThanTheColumn()
    {
        $saved = $this->logAndCaptureSavedMessage(str_repeat('a', 70 * 1024));

        $this->assertSame(65535, strlen($saved));
        $this->assertStringEndsWith("\n[cut: 71680 bytes]", $saved);
    }

    public function testLog_replacesInvalidUtf8()
    {
        $saved = $this->logAndCaptureSavedMessage("Caf\xED con leche");

        $this->assertSame('Caf? con leche', $saved);
    }

    public function testLog_writesNothingToTheResponseWhenSaveFails()
    {
        $table = $this->createMock(LogEntriesTable::class);
        $table->method('saveLog')->willThrowException(new \PDOException('Data too long'));
        $log = new DatabaseLog();
        $log->LogEntries = $table;

        $this->withoutErrorLog(fn () => $log->log(LogLevel::ERROR, 'request payload with personal data'));

        $this->expectOutputString('');
    }

    public function testStorableMessage_keepsShortValidMessage()
    {
        $this->assertSame('Fehler: Größe', DatabaseLog::storableMessage('Fehler: Größe'));
    }

    public function testStorableMessage_doesNotSplitAMultibyteCharacter()
    {
        $stored = DatabaseLog::storableMessage(str_repeat('ö', 40000));

        $this->assertLessThanOrEqual(65535, strlen($stored));
        $this->assertTrue(mb_check_encoding($stored, 'UTF-8'));
        $this->assertStringEndsWith("\n[cut: 80000 bytes]", $stored);
    }

    private function logAndCaptureSavedMessage(string $message): string
    {
        $saved = null;
        $table = $this->createMock(LogEntriesTable::class);
        $table->expects($this->once())->method('saveLog')
            ->willReturnCallback(function ($level, $message) use (&$saved) {
                $saved = $message;
            });
        $log = new DatabaseLog();
        $log->LogEntries = $table;

        $log->log(LogLevel::ERROR, $message);

        return $saved;
    }

    private function withoutErrorLog(callable $callback): void
    {
        $previous = ini_set('error_log', '/dev/null');
        try {
            $callback();
        } finally {
            ini_set('error_log', (string)$previous);
        }
    }
}
