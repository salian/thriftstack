<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
?>
<nav class="admin-nav">
    <a href="/workspace-admin/users" class="<?= $path === '/workspace-admin/users' ? 'is-active' : '' ?>">Workspace Users</a>
    <a href="/workspace-admin/analytics/credits" class="<?= $path === '/workspace-admin/analytics/credits' ? 'is-active' : '' ?>">Credits Analytics</a>
    <a href="/workspace-admin/audit" class="<?= $path === '/workspace-admin/audit' ? 'is-active' : '' ?>">Workspace Audit</a>
</nav>
