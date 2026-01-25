<?php

declare(strict_types=1);

final class NotificationDispatcher
{
    private Mailer $mailer;
    private ?callable $sender;

    public function __construct(array $config, ?Mailer $mailer = null, ?callable $sender = null)
    {
        $this->mailer = $mailer ?? new Mailer($config);
        $this->sender = $sender;
    }

    public function sendEmail(string $to, string $subject, string $body): bool
    {
        return $this->sendRaw($to, $subject, $body);
    }

    public function sendDigest(string $to, string $subject, array $notifications, string $appName): bool
    {
        $body = $this->buildDigestBody($notifications, $appName);
        return $this->sendRaw($to, $subject, $body);
    }

    public function buildDigestBody(array $notifications, string $appName): string
    {
        $lines = [];
        $lines[] = 'You have new notifications on ' . $appName . '.';
        $lines[] = '';

        foreach ($notifications as $notification) {
            $subject = (string)($notification['subject'] ?? '');
            $body = (string)($notification['body'] ?? '');
            $created = (string)($notification['created_at'] ?? '');
            $lines[] = '- ' . $subject . ($created !== '' ? ' (' . $created . ')' : '');
            if ($body !== '') {
                $lines[] = '  ' . $body;
            }
        }

        $lines[] = '';
        $lines[] = 'You can manage your preferences in your account settings.';

        return implode("\n", $lines);
    }

    private function sendRaw(string $to, string $subject, string $body): bool
    {
        if ($this->sender) {
            return (bool) call_user_func($this->sender, $to, $subject, $body);
        }

        return $this->mailer->send($to, $subject, $body);
    }
}
