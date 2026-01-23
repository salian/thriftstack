<?php

declare(strict_types=1);

final class ErrorHandler
{
    private Logger $logger;
    private bool $displayErrors;

    public function __construct(Logger $logger, bool $displayErrors)
    {
        $this->logger = $logger;
        $this->displayErrors = $displayErrors;
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $this->logger->error('PHP error', [
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
        ]);

        if ($this->displayErrors) {
            $this->renderError($message, $file, $line);
        }

        return true;
    }

    public function handleException(Throwable $exception): void
    {
        $this->logger->error('Unhandled exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        if ($this->displayErrors) {
            $this->renderException($exception);
        } else {
            http_response_code(500);
            echo 'An unexpected error occurred.';
        }
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $this->logger->error('Fatal error', [
            'type' => $error['type'] ?? null,
            'message' => $error['message'] ?? '',
            'file' => $error['file'] ?? '',
            'line' => $error['line'] ?? 0,
        ]);

        if ($this->displayErrors) {
            $this->renderError(
                (string)($error['message'] ?? ''),
                (string)($error['file'] ?? ''),
                (int)($error['line'] ?? 0)
            );
        }
    }

    private function renderException(Throwable $exception): void
    {
        http_response_code(500);
        echo '<h1>Application Error</h1>';
        echo '<p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }

    private function renderError(string $message, string $file, int $line): void
    {
        http_response_code(500);
        echo '<h1>Application Error</h1>';
        echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p>' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . ':' . $line . '</p>';
    }
}
