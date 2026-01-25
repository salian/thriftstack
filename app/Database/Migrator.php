<?php

declare(strict_types=1);

final class Migrator
{
    private PDO $pdo;
    private string $path;

    public function __construct(PDO $pdo, string $path)
    {
        $this->pdo = $pdo;
        $this->path = rtrim($path, '/');
    }

    public function run(): int
    {
        $this->ensureMigrationsTable();

        $applied = $this->appliedMigrations();
        $files = glob($this->path . '/*.php') ?: [];
        sort($files, SORT_STRING);

        $count = 0;
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = require $file;
            $callable = $this->resolveMigration($migration, $name);

            $this->pdo->beginTransaction();
            try {
                $callable($this->pdo);
                $stmt = $this->pdo->prepare('INSERT INTO migrations (filename, applied_at) VALUES (?, NOW())');
                $stmt->execute([$name]);
                if ($this->pdo->inTransaction()) {
                    $this->pdo->commit();
                }
                $count++;
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                $message = sprintf('Migration failed: %s | %s', $name, $e->getMessage());
                error_log($message);
                throw new RuntimeException($message, 0, $e);
            }
        }

        return $count;
    }

    private function resolveMigration($migration, string $name): callable
    {
        if (is_callable($migration)) {
            return $migration;
        }

        if (is_array($migration) && isset($migration['up']) && is_callable($migration['up'])) {
            return $migration['up'];
        }

        throw new RuntimeException('Migration ' . $name . ' must return a callable or array with up callable.');
    }

    private function ensureMigrationsTable(): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->pdo->exec($sql);
    }

    private function appliedMigrations(): array
    {
        $stmt = $this->pdo->query('SELECT filename FROM migrations');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
}
