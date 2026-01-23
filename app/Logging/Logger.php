<?php

declare(strict_types=1);

final class Logger
{
    private string $logFile;

    private const LEVELS = ['debug', 'info', 'warning', 'error'];

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        if (!in_array($level, self::LEVELS, true)) {
            $level = 'info';
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextJson = $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        $line = sprintf('[%s] %s: %s%s', $timestamp, strtoupper($level), $message, $contextJson ? ' ' . $contextJson : '');

        error_log($line);
        $this->writeToFile($line);
    }

    private function writeToFile(string $line): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
