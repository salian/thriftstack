<?php

declare(strict_types=1);

final class ReportPreferencesController
{
    private PDO $pdo;
    private WorkspaceSettingsService $settings;
    private WorkspaceService $workspaces;

    public function __construct(PDO $pdo, WorkspaceSettingsService $settings, WorkspaceService $workspaces)
    {
        $this->pdo = $pdo;
        $this->settings = $settings;
        $this->workspaces = $workspaces;
    }

    public function index(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspace = $this->workspaces->ensureCurrentWorkspace($userId);
        if (!$workspace) {
            return Response::redirect('/teams');
        }

        $settings = $this->settings->getSettings((int)$workspace['id']);

        return Response::html(View::render('settings/reports', [
            'title' => 'Report Preferences',
            'workspace' => $workspace,
            'settings' => $settings['reports'] ?? [],
            'message' => $request->query('saved') ? 'Report preferences updated.' : null,
        ]));
    }

    public function update(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspace = $this->workspaces->ensureCurrentWorkspace($userId);
        if (!$workspace) {
            return Response::redirect('/teams');
        }

        $workspaceId = (int)$workspace['id'];
        $settings = $this->settings->getSettings($workspaceId);
        $reports = $settings['reports'] ?? [];

        $frequency = (string)$request->input('digest_frequency', 'off');
        if (!in_array($frequency, ['off', 'weekly', 'monthly'], true)) {
            $frequency = 'off';
        }

        $includeMetrics = (array)($request->input('include_metrics', []));
        $allowedMetrics = ['credit_usage_summary', 'depletion_forecast', 'top_categories', 'cost_breakdown'];
        $filteredMetrics = array_values(array_intersect($allowedMetrics, $includeMetrics));

        $rawRecipients = (string)$request->input('recipients', '');
        $recipients = $this->parseRecipients($rawRecipients);

        $reports['digest_frequency'] = $frequency;
        $reports['include_metrics'] = $filteredMetrics;
        $reports['recipients'] = $recipients;

        $settings['reports'] = $reports;
        $this->settings->saveSettings($workspaceId, $settings);

        return Response::redirect('/settings/reports?saved=1');
    }

    private function parseRecipients(string $raw): array
    {
        $parts = preg_split('/[\s,]+/', strtolower($raw), -1, PREG_SPLIT_NO_EMPTY);
        $emails = [];
        foreach ($parts as $email) {
            $email = trim($email);
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $emails[] = $email;
        }
        $emails = array_values(array_unique($emails));
        return $emails;
    }
}
