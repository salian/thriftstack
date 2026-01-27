<?php

declare(strict_types=1);

final class PaymentGatewaysController
{
    private PDO $pdo;
    private PaymentGatewaySettingsService $gateways;
    private AppSettingsService $appSettings;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->gateways = new PaymentGatewaySettingsService($pdo);
        $this->appSettings = new AppSettingsService($pdo);
    }

    public function index(Request $request): Response
    {
        $settings = $this->gateways->getAllSettings();
        $rule = $this->appSettings->get('billing.gateway_rule', 'priority');
        $priority = $this->appSettings->getJson('billing.gateway_priority', [
            'stripe',
            'razorpay',
            'paypal',
            'lemonsqueezy',
            'dodo',
            'paddle',
        ]);

        return Response::html(View::render('admin/payment_gateways/index', [
            'title' => 'Payment Gateways',
            'settings' => $settings,
            'gatewayRule' => $rule,
            'gatewayPriority' => $priority,
        ]));
    }

    public function save(Request $request, string $provider): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $allowed = ['stripe', 'razorpay', 'paypal', 'lemonsqueezy', 'dodo', 'paddle'];
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

        $this->gateways->saveProviderSettings($provider, $payload);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => ucfirst($provider) . ' settings saved.',
        ];

        return Response::redirect('/super-admin/payment-gateways');
    }

    public function saveRules(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $rule = (string)$request->input('gateway_rule', 'priority');
        $allowed = ['priority', 'random', 'failure_rate', 'geo'];
        if (!in_array($rule, $allowed, true)) {
            $rule = 'priority';
        }

        $priority = $request->input('gateway_priority', []);
        $priorityList = [];
        if (is_array($priority)) {
            foreach ($priority as $provider) {
                $provider = (string)$provider;
                if ($provider === '') {
                    continue;
                }
                $priorityList[] = $provider;
            }
        }
        if (empty($priorityList)) {
            $priorityList = ['stripe', 'razorpay', 'paypal', 'lemonsqueezy'];
        }

        $this->appSettings->set('billing.gateway_rule', $rule);
        $this->appSettings->set('billing.gateway_priority', json_encode($priorityList));

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Gateway selection rules saved.',
        ];

        return Response::redirect('/super-admin/payment-gateways');
    }

    private function fieldsForProvider(string $provider): array
    {
        $map = [
            'stripe' => ['enabled', 'webhook_secret', 'publishable_key', 'secret_key'],
            'razorpay' => ['enabled', 'webhook_secret', 'key_id', 'key_secret'],
            'paypal' => ['enabled', 'webhook_id', 'client_id', 'client_secret', 'mode'],
            'lemonsqueezy' => ['enabled', 'webhook_secret', 'api_key', 'store_id'],
            'dodo' => ['enabled', 'webhook_secret', 'api_key', 'environment'],
            'paddle' => ['enabled', 'endpoint_secret', 'api_key', 'client_token', 'environment'],
        ];

        return $map[$provider] ?? [];
    }
}
