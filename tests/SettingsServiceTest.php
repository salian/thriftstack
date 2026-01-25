<?php

declare(strict_types=1);

require __DIR__ . '/../app/Settings/SettingsService.php';

final class SettingsServiceTest extends TestCase
{
    public function run(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('CREATE TABLE user_settings (user_id INTEGER PRIMARY KEY, settings_json TEXT NOT NULL, updated_at TEXT NOT NULL)');

        $service = new SettingsService($pdo);
        $defaults = $service->getSettings(1);
        $this->assertTrue($defaults['notify_email'], 'Default notify_email should be true');

        $service->saveSettings(1, [
            'notify_email' => false,
            'notify_in_app' => false,
            'notify_digest' => true,
        ]);

        $settings = $service->getSettings(1);
        $this->assertFalse($settings['notify_email'], 'notify_email should be false');
        $this->assertTrue($settings['notify_digest'], 'notify_digest should be true');
    }
}
