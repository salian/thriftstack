<?php

declare(strict_types=1);

require __DIR__ . '/../app/Auth/Csrf.php';

final class CsrfTest extends TestCase
{
    public function run(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = Csrf::token();
        $this->assertNotEmpty($token, 'CSRF token not generated');
        $this->assertTrue(Csrf::validate($token), 'CSRF token not validated');
        $this->assertFalse(Csrf::validate('invalid'), 'CSRF token validation should fail');
    }
}
