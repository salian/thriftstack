<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$financialActive = $path === '/super-admin/analytics/financial';
$settingsActive = $path === '/super-admin/settings' || str_starts_with($path, '/super-admin/user-roles');
$billingPlansActive = $path === '/super-admin/billing-plans';
$paymentGatewaysActive = $path === '/super-admin/payment-gateways';
?>
<nav class="admin-nav">
    <a href="/super-admin/analytics" class="<?= $path === '/super-admin/analytics' ? 'is-active' : '' ?>">Analytics</a>
    <a href="/super-admin/analytics/financial" class="<?= $financialActive ? 'is-active' : '' ?>">Financial Analytics</a>
    <a href="/super-admin/usage" class="<?= $path === '/super-admin/usage' ? 'is-active' : '' ?>">Usage</a>
    <a href="/super-admin/settings" class="<?= $settingsActive ? 'is-active' : '' ?>">Site Settings</a>
    <a href="/super-admin/billing-plans" class="<?= $billingPlansActive ? 'is-active' : '' ?>">Billing Plans</a>
    <a href="/super-admin/payment-gateways" class="<?= $paymentGatewaysActive ? 'is-active' : '' ?>">Payment Gateways</a>
</nav>
