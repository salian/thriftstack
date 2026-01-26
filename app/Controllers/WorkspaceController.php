<?php

declare(strict_types=1);

final class WorkspaceController
{
    private WorkspaceService $service;

    public function __construct(WorkspaceService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        return $this->renderIndex($userId, null, null, null);
    }

    public function create(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            return $this->renderIndex($userId, null, 'Workspace name is required.', null);
        }

        $this->service->createWorkspace($name, $userId);

        return $this->renderIndex($userId, 'Workspace created.', null, null);
    }

    public function switch(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspaceId = (int)$request->input('workspace_id', 0);
        if ($workspaceId <= 0) {
            return $this->renderIndex($userId, null, 'Select a workspace to switch.', null);
        }

        $role = $this->service->membershipRole($userId, $workspaceId);
        if ($role === null) {
            return $this->renderIndex($userId, null, 'You are not a member of that workspace.', null);
        }

        $this->service->setCurrentWorkspace($workspaceId);
        $workspace = $this->service->getWorkspace($workspaceId);
        $workspaceName = $workspace['name'] ?? 'workspace';
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Switched to ' . $workspaceName . '.',
        ];

        $returnTo = (string)$request->input('return_to', '');
        if ($returnTo === '' || !str_starts_with($returnTo, '/')) {
            $returnTo = '/teams';
        }

        return Response::redirect($returnTo);
    }

    public function updateMemberRole(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspaceId = $this->service->currentWorkspaceId() ?? 0;
        $memberId = (int)$request->input('member_id', 0);
        $role = (string)$request->input('role', 'Workspace Member');

        if ($workspaceId <= 0 || $memberId <= 0) {
            return $this->renderIndex($userId, null, 'Unable to update member role.', null);
        }

        $allowedRoles = ['Workspace Owner', 'Workspace Admin', 'Workspace Member'];
        if (!in_array($role, $allowedRoles, true)) {
            return $this->renderIndex($userId, null, 'Invalid role selected.', null);
        }

        $updated = $this->service->changeMemberRole($workspaceId, $memberId, $role, $userId);
        if (!$updated) {
            return $this->renderIndex($userId, null, 'Member role update failed.', null);
        }

        return $this->renderIndex($userId, 'Member role updated.', null, null);
    }

    public function updateName(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspaceId = (int)$request->input('workspace_id', 0);
        $name = trim((string)$request->input('name', ''));

        if ($workspaceId <= 0 || $name === '') {
            return $this->renderIndex($userId, null, 'Workspace name is required.', null);
        }

        if (strlen($name) < 2 || strlen($name) > 120) {
            return $this->renderIndex($userId, null, 'Workspace name must be 2 to 120 characters.', null);
        }

        $role = $this->service->membershipRole($userId, $workspaceId);
        if ($role === null || !$this->service->isRoleAtLeast($role, 'Workspace Admin')) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $this->service->updateWorkspaceName($workspaceId, $name, $userId);

        $returnTo = (string)$request->input('return_to', '');
        if ($returnTo === '' || !str_starts_with($returnTo, '/')) {
            $returnTo = '/teams';
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Workspace name updated.',
        ];

        return Response::redirect($returnTo);
    }

    public function renderIndex(
        int $userId,
        ?string $message,
        ?string $error,
        ?string $inviteLink
    ): Response {
        $workspaces = $this->service->listForUser($userId);
        $currentWorkspace = $this->service->ensureCurrentWorkspace($userId);
        $currentRole = null;
        $members = [];
        $invites = [];

        if ($currentWorkspace) {
            $currentRole = $this->service->membershipRole($userId, (int)$currentWorkspace['id']);
            $members = $this->service->listMembers((int)$currentWorkspace['id']);
            $invites = $this->service->listInvites((int)$currentWorkspace['id']);
        }

        $roles = ['Workspace Owner', 'Workspace Admin', 'Workspace Member'];
        $canManage = $currentRole !== null && $this->service->isRoleAtLeast($currentRole, 'Workspace Admin');

        return Response::html(View::render('workspaces/index', [
            'title' => 'Teams',
            'message' => $message,
            'error' => $error,
            'inviteLink' => $inviteLink,
            'workspaces' => $workspaces,
            'currentWorkspace' => $currentWorkspace,
            'currentRole' => $currentRole,
            'members' => $members,
            'invites' => $invites,
            'roles' => $roles,
            'canManage' => $canManage,
        ]));
    }
}
