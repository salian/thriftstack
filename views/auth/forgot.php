<section class="auth">
    <h1>Reset password</h1>
    <p>We'll email you a reset link.</p>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="post" action="/forgot" class="form">
        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
        <label>
            <span>Email</span>
            <input type="email" name="email" autocomplete="email" required>
        </label>
        <button type="submit" class="button">Send reset link</button>
    </form>

    <div class="auth-links">
        <a href="/login">Back to login</a>
    </div>
</section>
