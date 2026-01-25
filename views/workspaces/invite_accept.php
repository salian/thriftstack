<section class="page-section">
    <h1>Workspace invite</h1>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($invite)) : ?>
        <div class="card">
            <h2>Invite details</h2>
            <p><strong>Workspace:</strong> <?= e($invite['workspace_name'] ?? '') ?></p>
            <p><strong>Role:</strong> <?= e($invite['role'] ?? '') ?></p>
            <p><strong>Invited email:</strong> <?= e($invite['email'] ?? '') ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($requiresLogin)) : ?>
        <p>Please log in to accept this invite.</p>
        <a href="/login" class="button">Login</a>
    <?php elseif (!empty($token) && empty($error)) : ?>
        <form method="post" action="/workspaces/invites/accept" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <button type="submit" class="button">Accept invite</button>
        </form>
    <?php else : ?>
        <a href="/workspaces" class="button">Back to workspaces</a>
    <?php endif; ?>
</section>
