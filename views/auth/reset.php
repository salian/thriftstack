<section class="auth">
    <h1>Set new password</h1>
    <p>Choose a new password for your account.</p>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="post" action="/reset" class="form">
        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
        <label>
            <span>New password</span>
            <input type="password" name="password" autocomplete="new-password" required>
        </label>
        <button type="submit" class="button">Update password</button>
    </form>

    <div class="auth-links">
        <a href="/login">Back to login</a>
    </div>
</section>
