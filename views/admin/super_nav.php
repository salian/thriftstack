<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$settingsActive = $path === '/super-admin/settings' || str_starts_with($path, '/super-admin/user-roles');
?>
<nav class="admin-nav">
    <a href="/super-admin/analytics" class="<?= $path === '/super-admin/analytics' ? 'is-active' : '' ?>">Analytics</a>
    <a href="/super-admin/usage" class="<?= $path === '/super-admin/usage' ? 'is-active' : '' ?>">Usage</a>
    <a href="/super-admin/settings" class="<?= $settingsActive ? 'is-active' : '' ?>">Site Settings</a>
</nav>
