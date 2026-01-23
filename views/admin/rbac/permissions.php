<section class="page-section">
    <h1>Permissions</h1>
    <p>Define permissions for your modules.</p>

    <?php require __DIR__ . '/../nav.php'; ?>

    <div class="card">
        <h2>Create permission</h2>
        <form method="post" action="/admin/permissions" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Permission name</span>
                <input type="text" name="name" required>
            </label>
            <label>
                <span>Description</span>
                <input type="text" name="description">
            </label>
            <button type="submit" class="button">Create permission</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing permissions</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissions as $permission) : ?>
                    <tr>
                        <td><?= e($permission['name']) ?></td>
                        <td><?= e($permission['description'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
