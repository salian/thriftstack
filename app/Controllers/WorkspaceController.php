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
        return $this->renderIndex($request, $userId, null, null, null);
    }

    public function create(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            return $this->renderIndex($request, $userId, null, 'Workspace name is required.', null);
        }

        $this->service->createWorkspace($name, $userId);

        return $this->renderIndex($request, $userId, 'Workspace created.', null, null);
    }

    public function switch(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $workspaceId = (int)$request->input('workspace_id', 0);
        if ($workspaceId <= 0) {
            return $this->renderIndex($request, $userId, null, 'Select a workspace to switch.', null);
        }

        $role = $this->service->membershipRole($userId, $workspaceId);
        if ($role === null) {
            return $this->renderIndex($request, $userId, null, 'You are not a member of that workspace.', null);
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
        $returnTo = (string)$request->input('return_to', '/teams');
        if ($returnTo === '' || !str_starts_with($returnTo, '/teams')) {
            $returnTo = '/teams';
        }
        $workspaceId = $this->service->currentWorkspaceId() ?? 0;
        $memberId = (int)$request->input('member_id', 0);
        $role = (string)$request->input('role', 'Workspace Member');

        if ($workspaceId <= 0 || $memberId <= 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Unable to update member role.',
            ];
            return Response::redirect($returnTo);
        }

        $allowedRoles = ['Workspace Owner', 'Workspace Admin', 'Workspace Member'];
        if (!in_array($role, $allowedRoles, true)) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Invalid role selected.',
            ];
            return Response::redirect($returnTo);
        }

        if ($memberId === $userId && $role !== 'Workspace Owner') {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Workspace owners cannot change their own role. Assign another owner first.',
            ];
            return Response::redirect($returnTo);
        }

        $updated = $this->service->changeMemberRole($workspaceId, $memberId, $role, $userId);
        if (!$updated) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Member role update failed.',
            ];
            return Response::redirect($returnTo);
        }

        return $this->renderIndex($request, $userId, 'Member role updated.', null, null);
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
            return $this->renderIndex($request, $userId, null, 'Workspace name is required.', null);
        }

        if (strlen($name) < 2 || strlen($name) > 120) {
            return $this->renderIndex($request, $userId, null, 'Workspace name must be 2 to 120 characters.', null);
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
        Request $request,
        int $userId,
        ?string $message,
        ?string $error,
        ?string $inviteLink
    ): Response {
        $memberSearch = trim((string)$request->query('member_search', ''));
        $memberRole = (string)$request->query('member_role', 'all');
        $memberPage = max(1, (int)$request->query('member_page', 1));
        $workspaceSearch = trim((string)$request->query('workspace_search', ''));
        $workspaceRole = (string)$request->query('workspace_role', 'all');
        $workspacePage = max(1, (int)$request->query('workspace_page', 1));
        $perPage = 10;

        $workspacesTotal = $this->service->countWorkspacesForUser($userId, $workspaceSearch, $workspaceRole);
        $workspaceTotalPages = max(1, (int)ceil($workspacesTotal / $perPage));
        $workspacePage = min($workspacePage, $workspaceTotalPages);
        $workspacesOffset = ($workspacePage - 1) * $perPage;
        $workspaces = $this->service->listWorkspacesForUser($userId, $workspaceSearch, $workspaceRole, $perPage, $workspacesOffset);

        $currentWorkspace = $this->service->ensureCurrentWorkspace($userId);
        $currentRole = null;
        $teamEntries = [];
        $invites = [];
        $memberTotal = 0;
        $memberTotalPages = 1;
        $memberPage = max(1, $memberPage);

        if ($currentWorkspace) {
            $currentRole = $this->service->membershipRole($userId, (int)$currentWorkspace['id']);
            $workspaceId = (int)$currentWorkspace['id'];
            $memberTotal = $this->service->countTeamEntries($workspaceId, $memberSearch, $memberRole);
            $memberTotalPages = max(1, (int)ceil($memberTotal / $perPage));
            $memberPage = min($memberPage, $memberTotalPages);
            $memberOffset = ($memberPage - 1) * $perPage;
            $teamEntries = $this->service->listTeamEntries($workspaceId, $memberSearch, $memberRole, $perPage, $memberOffset);
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
            'workspacesTotal' => $workspacesTotal,
            'workspaceSearch' => $workspaceSearch,
            'workspaceRole' => $workspaceRole,
            'workspacePage' => $workspacePage,
            'workspaceTotalPages' => $workspaceTotalPages,
            'currentWorkspace' => $currentWorkspace,
            'currentRole' => $currentRole,
            'teamEntries' => $teamEntries,
            'invites' => $invites,
            'memberSearch' => $memberSearch,
            'memberRole' => $memberRole,
            'memberPage' => $memberPage,
            'memberTotalPages' => $memberTotalPages,
            'memberTotal' => $memberTotal,
            'roles' => $roles,
            'canManage' => $canManage,
        ]));
    }
}
