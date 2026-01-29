<section class="page-section">
    <h1>API Tokens</h1>
    <p>Create and revoke API tokens for this workspace.</p>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($newToken)) : ?>
        <div class="card">
            <h2>New token</h2>
            <p class="muted">Copy this token now. You will not be able to view it again.</p>
            <div class="helper-copy">
                <code><?= e($newToken) ?></code>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Create API token</h2>
        <form method="post" action="/settings/api-tokens" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Name</span>
                <input type="text" name="name" required>
            </label>
            <label>
                <span>Scopes (comma-separated)</span>
                <input type="text" name="scopes" placeholder="credits.consume">
            </label>
            <label>
                <span>Expires at (optional)</span>
                <input type="date" name="expires_at">
            </label>
            <button type="submit" class="button">Create token</button>
        </form>
    </div>

    <div class="card">
        <h2>Active tokens</h2>
        <?php if (empty($tokens)) : ?>
            <p>No tokens created yet.</p>
        <?php else : ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Scopes</th>
                        <th>Last used</th>
                        <th>Expires</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $token) : ?>
                        <tr>
                            <td><?= e($token['name'] ?? '') ?></td>
                            <td><?= e($token['scopes'] ?? '') ?></td>
                            <td><?= e($token['last_used_at'] ?? '—') ?></td>
                            <td><?= e($token['expires_at'] ?? '—') ?></td>
                            <td><?= e($token['created_at'] ?? '') ?></td>
                            <td>
                                <form method="post" action="/settings/api-tokens/revoke">
                                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                    <input type="hidden" name="token_id" value="<?= e((string)$token['id']) ?>">
                                    <button type="submit" class="button button-ghost">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
