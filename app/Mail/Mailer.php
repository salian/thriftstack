<?php

declare(strict_types=1);

final class Mailer
{
    private string $fromName;
    private string $fromEmail;

    public function __construct(array $config)
    {
        $mail = $config['mail'] ?? [];
        $this->fromName = $mail['from_name'] ?? ($config['app']['name'] ?? 'ThriftStack');
        $this->fromEmail = $mail['from_email'] ?? 'no-reply@example.com';
    }

    public function send(string $toEmail, string $subject, string $message): bool
    {
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return mail($toEmail, $subject, $message, implode("\r\n", $headers));
    }
}
