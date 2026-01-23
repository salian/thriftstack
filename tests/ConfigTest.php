<?php

declare(strict_types=1);

final class ConfigTest extends TestCase
{
    public function run(): void
    {
        $config = require __DIR__ . '/../config.php';

        $this->assertEquals('app', array_key_exists('app', $config) ? 'app' : 'missing', 'Missing app config');
        $this->assertEquals('db', array_key_exists('db', $config) ? 'db' : 'missing', 'Missing db config');
        $this->assertEquals('mail', array_key_exists('mail', $config) ? 'mail' : 'missing', 'Missing mail config');
        $this->assertEquals('security', array_key_exists('security', $config) ? 'security' : 'missing', 'Missing security config');
        $this->assertEquals('auth', array_key_exists('auth', $config) ? 'auth' : 'missing', 'Missing auth config');
        $this->assertNotEmpty($config['app']['env'] ?? null, 'Missing app.env');
    }
}
