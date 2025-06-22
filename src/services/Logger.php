<?php

namespace SimpleApiRest\services;

readonly class Logger
{

    public function __construct(private string $logFile, private int $maxSize = 1024 * 1024)
    {
        $this->rotateLog();
    }

    private function rotateLog(): void
    {
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxSize) {
            rename($this->logFile, $this->logFile . '.' . time());
        }
    }

    private function log(string $level, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] PHP $level: $message" . PHP_EOL, FILE_APPEND);
    }

    public function notice(string $message): void
    {
        $this->log('NOTICE', $message);
    }

    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->log('WARNING', $message);
    }

    public function error(string $message): void
    {
        $this->log('ERROR', $message);
    }

}