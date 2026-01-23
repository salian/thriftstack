<?php

declare(strict_types=1);

final class LogRotation
{
    public static function rotate(string $logFile, int $maxBytes = 5242880, int $maxAgeDays = 30): void
    {
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        if (is_file($logFile) && filesize($logFile) !== false && filesize($logFile) > $maxBytes) {
            $timestamp = date('YmdHis');
            $rotated = $dir . '/app-' . $timestamp . '.log';
            rename($logFile, $rotated);
        }

        $cutoff = time() - ($maxAgeDays * 86400);
        $files = glob($dir . '/app-*.log') ?: [];
        foreach ($files as $file) {
            $modified = filemtime($file);
            if ($modified !== false && $modified < $cutoff) {
                unlink($file);
            }
        }
    }
}
