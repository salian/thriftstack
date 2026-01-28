<?php
$settings = new AppSettingsService(DB::connect($GLOBALS['config'] ?? []));
$companyName = $settings->get('invoice.company_name', 'Your Company');
$supportEmail = $settings->get('invoice.support_email', 'support@example.com');
$redactedEmail = str_replace(['@', '.'], [' [at] ', ' [dot] '], $supportEmail ?? 'support@example.com');
$addressParts = array_filter([
    $settings->get('invoice.company_address', ''),
    $settings->get('invoice.company_city', ''),
    $settings->get('invoice.company_state', ''),
    $settings->get('invoice.company_postal_code', ''),
    $settings->get('invoice.company_country', ''),
]);
$companyAddress = $addressParts ? implode(', ', $addressParts) : '';
?>
<section class="page-section">
    <h1>Terms of Service</h1>
    <p>Effective date: <?= e(date('F j, Y')) ?></p>

    <p>By using <?= e((string)config('app.name', 'ThriftStack')) ?>, you agree to these terms.</p>

    <h2>Accounts</h2>
    <p>You are responsible for your account security and all activity that occurs under your account.</p>

    <h2>Subscriptions and Billing</h2>
    <p>Paid plans, trials, renewals, and refunds are governed by the plan you select and applicable payment
        provider rules. We may suspend or terminate access for non-payment.</p>

    <h2>Acceptable Use</h2>
    <p>Do not misuse the service, attempt unauthorized access, or use the service for unlawful purposes.</p>

    <h2>Content and Ownership</h2>
    <p>You retain ownership of your content. You grant us permission to host and process it to provide the
        service.</p>

    <h2>Termination</h2>
    <p>We may suspend or terminate accounts that violate these terms or pose security risks.</p>

    <h2>Disclaimers</h2>
    <p>The service is provided "as is" without warranties of any kind.</p>

    <h2>Contact</h2>
    <p>Company: <?= e($companyName ?? 'Your Company') ?></p>
    <?php if ($companyAddress !== '') : ?>
        <p>Address: <?= e($companyAddress) ?></p>
    <?php endif; ?>
    <p>Email: <?= e($redactedEmail ?? 'support [at] example [dot] com') ?></p>
</section>
