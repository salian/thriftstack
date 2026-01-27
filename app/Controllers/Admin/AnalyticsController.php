<?php

declare(strict_types=1);

final class AnalyticsController
{
    public function index(Request $request): Response
    {
        $user = $request->session('user');
        $isAdmin = is_array($user) ? (int)($user['is_system_admin'] ?? 0) === 1 : false;

        $kpis = [
            ['label' => 'Monthly Active Users', 'value' => '1,284', 'delta' => '+8%'],
            ['label' => 'Active Workspaces', 'value' => '214', 'delta' => '+3%'],
            ['label' => 'MRR', 'value' => '$12,480', 'delta' => '+5%'],
            ['label' => 'Churn', 'value' => '2.4%', 'delta' => '-0.4%'],
        ];

        $charts = [
            [
                'title' => 'Usage over time',
                'description' => 'Active users and sessions by week.',
            ],
            [
                'title' => 'Revenue by plan',
                'description' => 'MRR split by plan tier and trial conversions.',
            ],
            [
                'title' => 'Workspace retention',
                'description' => 'New vs retained workspaces by cohort.',
            ],
        ];

        if (!$isAdmin) {
            $kpis = array_values(array_filter($kpis, static function (array $kpi): bool {
                return $kpi['label'] !== 'MRR';
            }));
            $charts = array_values(array_filter($charts, static function (array $chart): bool {
                return $chart['title'] !== 'Revenue by plan';
            }));
        }

        $futureSources = [
            'Billing providers (Stripe, Razorpay, PayPal, Lemon Squeezy)',
            'Workspace activity events and feature usage',
            'Audit logs and notification delivery metrics',
        ];

        return Response::html(View::render('admin/analytics/index', [
            'title' => 'App Analytics',
            'kpis' => $kpis,
            'charts' => $charts,
            'futureSources' => $futureSources,
            'showRevenue' => $isAdmin,
        ]));
    }
}
