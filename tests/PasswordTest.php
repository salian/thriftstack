<?php

declare(strict_types=1);

require __DIR__ . '/../app/Auth/Password.php';

final class PasswordTest extends TestCase
{
    public function run(): void
    {
        $hash = Password::hash('secret123');
        $this->assertNotEmpty($hash, 'Password hash missing');
        $this->assertTrue(Password::verify('secret123', $hash), 'Password hash should verify');
        $this->assertFalse(Password::verify('wrong', $hash), 'Password hash should reject wrong password');
    }
}
