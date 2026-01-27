<?php

declare(strict_types=1);

require __DIR__ . '/../app/Audit/Audit.php';
require __DIR__ . '/../app/Workspaces/WorkspaceService.php';

final class WorkspaceServiceTest extends TestCase
{
    public function run(): void
    {
        $_SESSION = [];

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)');
        $pdo->exec('CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, workspace_id INTEGER, action TEXT, metadata TEXT, created_at TEXT)');
        $pdo->exec('CREATE TABLE workspaces (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, created_by INTEGER, ai_credit_balance INTEGER NOT NULL DEFAULT 0, created_at TEXT)');
        $pdo->exec('CREATE TABLE workspace_memberships (user_id INTEGER, workspace_id INTEGER, workspace_role TEXT, created_at TEXT, PRIMARY KEY (user_id, workspace_id))');
        $pdo->exec('CREATE TABLE workspace_invites (id INTEGER PRIMARY KEY AUTOINCREMENT, workspace_id INTEGER, email TEXT, workspace_role TEXT, token_hash TEXT, expires_at TEXT, created_at TEXT, accepted_at TEXT)');

        $pdo->exec("INSERT INTO users (name, email) VALUES ('Owner', 'owner@example.com')");
        $pdo->exec("INSERT INTO users (name, email) VALUES ('Member', 'member@example.com')");

        $service = new WorkspaceService($pdo, new Audit($pdo));
        $workspaceId = $service->createWorkspace('Acme', 1);

        $this->assertTrue($workspaceId > 0, 'Workspace not created');
        $this->assertEquals('Workspace Owner', $service->membershipRole(1, $workspaceId), 'Owner role missing');

        $token = $service->createInvite($workspaceId, 'member@example.com', 'Workspace Member', 1);
        $this->assertNotEmpty($token, 'Invite token missing');

        $result = $service->acceptInvite($token, 2);
        $this->assertTrue((bool)$result['ok'], 'Invite not accepted');
        $this->assertEquals('Workspace Member', $service->membershipRole(2, $workspaceId), 'Member role missing');

        $token2 = $service->createInvite($workspaceId, 'member2@example.com', 'Workspace Member', 1);
        $this->assertNotEmpty($token2, 'Second invite token missing');
        $inviteRow = $pdo->query('SELECT id FROM workspace_invites ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $resent = $service->resendInvite((int)($inviteRow['id'] ?? 0), $workspaceId, 1);
        $this->assertTrue(!empty($resent['token']), 'Resend invite failed');

        $pdo->exec("INSERT INTO users (name, email) VALUES ('Solo', 'solo@example.com')");
        $defaultWorkspaceId = $service->ensureWorkspaceForUser(3, 'Solo User');
        $this->assertTrue($defaultWorkspaceId > 0, 'Default workspace not created');
        $this->assertEquals('Workspace Owner', $service->membershipRole(3, $defaultWorkspaceId), 'Default workspace membership missing');
    }
}
