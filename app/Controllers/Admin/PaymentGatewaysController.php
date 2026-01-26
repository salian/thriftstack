<?php

declare(strict_types=1);

final class PaymentGatewaysController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(Request $request): Response
    {
        $settings = $this->fetchSettings();

        return Response::html(View::render('admin/payment_gateways/index', [
            'title' => 'Payment Gateways',
            'settings' => $settings,
        ]));
    }

    public function save(Request $request, string $provider): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $allowed = ['stripe', 'razorpay', 'paypal', 'lemonsqueezy'];
        if (!in_array($provider, $allowed, true)) {
            return Response::notFound(View::render('404', ['title' => 'Not Found']));
        }

        $fields = $this->fieldsForProvider($provider);
        $payload = [];
        $post = $request->all();
        foreach ($fields as $field) {
            if (!array_key_exists($field, $post)) {
                continue;
            }
            $payload[$field] = trim((string)$request->input($field, ''));
        }

        $this->saveSettings($provider, $payload);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => ucfirst($provider) . ' settings saved.',
        ];

        return Response::redirect('/super-admin/payment-gateways');
    }

    private function fetchSettings(): array
    {
        $stmt = $this->pdo->query('SELECT provider, setting_key, setting_value FROM payment_gateway_settings');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $settings = [];
        foreach ($rows as $row) {
            $provider = (string)($row['provider'] ?? '');
            $key = (string)($row['setting_key'] ?? '');
            if ($provider === '' || $key === '') {
                continue;
            }
            $settings[$provider][$key] = (string)($row['setting_value'] ?? '');
        }

        return $settings;
    }

    private function fieldsForProvider(string $provider): array
    {
        $map = [
            'stripe' => ['enabled', 'webhook_secret', 'publishable_key', 'secret_key'],
            'razorpay' => ['enabled', 'webhook_secret', 'key_id', 'key_secret'],
            'paypal' => ['enabled', 'webhook_id', 'client_id', 'client_secret'],
            'lemonsqueezy' => ['enabled', 'webhook_secret', 'api_key', 'store_id'],
        ];

        return $map[$provider] ?? [];
    }

    private function saveSettings(string $provider, array $payload): void
    {
        $now = date('Y-m-d H:i:s');
        $insert = $this->pdo->prepare(
            'INSERT INTO payment_gateway_settings (provider, setting_key, setting_value, updated_at)
             VALUES (?, ?, ?, ?)'
        );
        $update = $this->pdo->prepare(
            'UPDATE payment_gateway_settings SET setting_value = ?, updated_at = ? WHERE provider = ? AND setting_key = ?'
        );
        $exists = $this->pdo->prepare(
            'SELECT COUNT(*) FROM payment_gateway_settings WHERE provider = ? AND setting_key = ?'
        );

        foreach ($payload as $key => $value) {
            if ($value === '') {
                $delete = $this->pdo->prepare('DELETE FROM payment_gateway_settings WHERE provider = ? AND setting_key = ?');
                $delete->execute([$provider, $key]);
                continue;
            }
            $exists->execute([$provider, $key]);
            $count = (int)$exists->fetchColumn();
            if ($count > 0) {
                $update->execute([$value, $now, $provider, $key]);
            } else {
                $insert->execute([$provider, $key, $value, $now]);
            }
        }
    }
}
