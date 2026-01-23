<section class="auth">
    <h1>Login</h1>
    <p>Access your dashboard.</p>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/login" class="form">
        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
        <label>
            <span>Email</span>
            <input type="email" name="email" autocomplete="email" required>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit" class="button">Login</button>
    </form>

    <div class="auth-links">
        <a href="/signup">Create an account</a>
        <a href="/forgot">Forgot password?</a>
    </div>
</section>
