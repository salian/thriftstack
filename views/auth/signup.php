<section class="auth">
    <h1>Create account</h1>
    <p>Start with a verified email address.</p>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/signup" class="form">
        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
        <label>
            <span>Name</span>
            <input type="text" name="name" autocomplete="name" required>
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="email" autocomplete="email" required>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" autocomplete="new-password" required>
        </label>
        <button type="submit" class="button">Create account</button>
    </form>

    <div class="auth-links">
        <a href="/login">Already have an account?</a>
    </div>
</section>
