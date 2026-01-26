<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec('UPDATE roles SET name = "Super Admin" WHERE name = "Admin"');
    $pdo->exec('UPDATE workspace_memberships SET role = "Workspace Admin" WHERE role = "Admin"');
    $pdo->exec('UPDATE workspace_invites SET role = "Workspace Admin" WHERE role = "Admin"');
};
