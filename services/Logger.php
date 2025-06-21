<?php

namespace MyApiRest\services;

class Logger
{

    private string $logFile;
    private int $maxSize;

    public function __construct(string $file, int $maxSize = 1024 * 1024)
    {
        $this->logFile = $file;
        $this->maxSize = $maxSize;
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
        $logMessage = "[$timestamp] PHP $level: $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
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