<?php

declare(strict_types=1);

final class WorkspaceInviteController
{
    private WorkspaceService $service;
    private WorkspaceController $workspaceController;
    private Mailer $mailer;
    private array $config;

    public function __construct(WorkspaceService $service, WorkspaceController $workspaceController, array $config)
    {
        $this->service = $service;
        $this->workspaceController = $workspaceController;
        $this->config = $config;
        $this->mailer = new Mailer($config);
    }

    public function create(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspaceId = $this->service->currentWorkspaceId() ?? 0;
        if ($workspaceId <= 0) {
            return $this->workspaceController->renderIndex($request, $userId, null, 'Select a workspace first.', null);
        }

        $email = trim((string)$request->input('email', ''));
        $role = (string)$request->input('role', 'Workspace Member');
        $allowedRoles = ['Workspace Owner', 'Workspace Admin', 'Workspace Member'];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->workspaceController->renderIndex($request, $userId, null, 'Valid email is required.', null);
        }

        if (!in_array($role, $allowedRoles, true)) {
            return $this->workspaceController->renderIndex($request, $userId, null, 'Invalid role selected.', null);
        }

        $token = $this->service->createInvite($workspaceId, $email, $role, $userId);
        $baseUrl = rtrim((string)($this->config['app']['url'] ?? ''), '/');
        $link = $baseUrl . '/teams/invites/accept?token=' . urlencode($token);

        $subject = 'Workspace invitation';
        $body = "You have been invited to join a workspace on "
            . ($this->config['app']['name'] ?? 'ThriftStack')
            . ".\n\nAccept the invite:\n{$link}\n\nIf you did not request this, ignore this email.";
        $this->mailer->send($email, $subject, $body);

        return $this->workspaceController->renderIndex(
            $request,
            $userId,
            'Invite sent. Share the invite link if needed.',
            null,
            $link
        );
    }

    public function showAccept(Request $request): Response
    {
        $token = (string)$request->query('token', '');
        if ($token === '') {
            return Response::html(View::render('workspaces/invite_accept', [
                'title' => 'Accept invite',
                'error' => 'Invite token missing.',
                'message' => null,
                'invite' => null,
                'token' => null,
                'requiresLogin' => false,
            ]), 400);
        }

        $invite = $this->service->lookupInvite($token);
        if (!$invite) {
            return Response::html(View::render('workspaces/invite_accept', [
                'title' => 'Accept invite',
                'error' => 'Invite token is invalid.',
                'message' => null,
                'invite' => null,
                'token' => null,
                'requiresLogin' => false,
            ]), 400);
        }

        $expired = strtotime((string)$invite['expires_at']) < time();
        $error = null;
        if (!empty($invite['accepted_at'])) {
            $error = 'Invite has already been accepted.';
        } elseif ($expired) {
            $error = 'Invite has expired.';
        }

        $requiresLogin = !Auth::check();

        return Response::html(View::render('workspaces/invite_accept', [
            'title' => 'Accept invite',
            'error' => $error,
            'message' => null,
            'invite' => $invite,
            'token' => $token,
            'requiresLogin' => $requiresLogin,
        ]));
    }

    public function accept(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $token = (string)$request->input('token', '');
        if ($token === '') {
            return Response::html(View::render('workspaces/invite_accept', [
                'title' => 'Accept invite',
                'error' => 'Invite token missing.',
                'message' => null,
                'invite' => null,
                'token' => null,
                'requiresLogin' => false,
            ]), 400);
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $result = $this->service->acceptInvite($token, $userId);

        if (!$result['ok']) {
            return Response::html(View::render('workspaces/invite_accept', [
                'title' => 'Accept invite',
                'error' => $result['error'] ?? 'Invite could not be accepted.',
                'message' => null,
                'invite' => $this->service->lookupInvite($token),
                'token' => $token,
                'requiresLogin' => false,
            ]), 400);
        }

        return Response::html(View::render('workspaces/invite_accept', [
            'title' => 'Invite accepted',
            'error' => null,
            'message' => 'Invite accepted. You can now access the workspace.',
            'invite' => $this->service->lookupInvite($token),
            'token' => null,
            'requiresLogin' => false,
        ]));
    }

    public function resend(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspaceId = $this->service->currentWorkspaceId() ?? 0;
        if ($workspaceId <= 0) {
            return $this->workspaceController->renderIndex($userId, null, 'Select a workspace first.', null);
        }

        $inviteId = (int)$request->input('invite_id', 0);
        if ($inviteId <= 0) {
            return $this->workspaceController->renderIndex($userId, null, 'Invite not found.', null);
        }

        $result = $this->service->resendInvite($inviteId, $workspaceId, $userId);
        if (!$result) {
            return $this->workspaceController->renderIndex($userId, null, 'Invite cannot be resent.', null);
        }

        $baseUrl = rtrim((string)($this->config['app']['url'] ?? ''), '/');
        $link = $baseUrl . '/teams/invites/accept?token=' . urlencode($result['token']);

        $subject = 'Workspace invitation';
        $body = "You have been invited to join a workspace on "
            . ($this->config['app']['name'] ?? 'ThriftStack')
            . ".\n\nAccept the invite:\n{$link}\n\nIf you did not request this, ignore this email.";
        $this->mailer->send((string)$result['email'], $subject, $body);

        return $this->workspaceController->renderIndex(
            $userId,
            'Invite resent.',
            null,
            $link
        );
    }
}
