<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec('UPDATE workspace_memberships SET role = "Workspace Owner" WHERE role = "Owner"');
    $pdo->exec('UPDATE workspace_memberships SET role = "Workspace Member" WHERE role = "Member"');
    $pdo->exec('UPDATE workspace_invites SET role = "Workspace Owner" WHERE role = "Owner"');
    $pdo->exec('UPDATE workspace_invites SET role = "Workspace Member" WHERE role = "Member"');
};
