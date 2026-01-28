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
    <h1>Privacy Policy</h1>
    <p>Effective date: <?= e(date('F j, Y')) ?></p>

    <p><?= e((string)config('app.name', 'ThriftStack')) ?> ("we", "us", "our") respects your privacy. This policy
        explains what data we collect, how we use it, and your rights.</p>

    <h2>Information We Collect</h2>
    <ul>
        <li>Account data: name, email, and authentication credentials.</li>
        <li>Usage data: pages visited, actions taken, and basic device/browser metadata.</li>
        <li>Support data: messages and files you submit to us.</li>
    </ul>

    <h2>How We Use Data</h2>
    <ul>
        <li>Provide, maintain, and secure the service.</li>
        <li>Process payments and enforce subscription terms.</li>
        <li>Improve product features and user experience.</li>
        <li>Respond to support requests and legal obligations.</li>
    </ul>

    <h2>Data Sharing</h2>
    <p>We only share data with trusted processors required to run the service (payment gateways, email delivery,
        analytics, and hosting). We do not sell personal data.</p>

    <h2>Data Retention</h2>
    <p>We retain data for as long as your account is active or as required by law. You may request deletion at any
        time.</p>

    <h2>Your Rights</h2>
    <p>You may access, correct, or delete your data by contacting us.</p>

    <h2>Contact</h2>
    <p>Company: <?= e($companyName ?? 'Your Company') ?></p>
    <?php if ($companyAddress !== '') : ?>
        <p>Address: <?= e($companyAddress) ?></p>
    <?php endif; ?>
    <p>Email: <?= e($redactedEmail ?? 'support [at] example [dot] com') ?></p>
</section>
