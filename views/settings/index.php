<section class="page-section">
    <h1>Settings</h1>
    <p>Update your profile and notification preferences.</p>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Profile</h2>
        <form method="post" action="/settings/profile" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" value="<?= e($user['email'] ?? '') ?>" disabled>
            </label>
            <button type="submit" class="button">Save profile</button>
        </form>
    </div>

    <div class="card">
        <h2>Notification preferences</h2>
        <form method="post" action="/settings/preferences" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label class="checkbox">
                <input type="checkbox" name="notify_email" <?= !empty($settings['notify_email']) ? 'checked' : '' ?>>
                <span>Email notifications</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="notify_in_app" <?= !empty($settings['notify_in_app']) ? 'checked' : '' ?>>
                <span>In-app notifications</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="notify_digest" <?= !empty($settings['notify_digest']) ? 'checked' : '' ?>>
                <span>Weekly digest</span>
            </label>
            <button type="submit" class="button">Save preferences</button>
        </form>
    </div>
</section>
