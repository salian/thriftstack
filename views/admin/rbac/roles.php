<section class="page-section">
    <h1>Roles</h1>
    <p>Manage roles and their permissions.</p>

    <?php require __DIR__ . '/../nav.php'; ?>

    <div class="card">
        <h2>Create role</h2>
        <form method="post" action="/admin/roles" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Role name</span>
                <input type="text" name="name" required>
            </label>
            <label>
                <span>Description</span>
                <input type="text" name="description">
            </label>
            <button type="submit" class="button">Create role</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing roles</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Description</th>
                    <th>Permissions</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role) : ?>
                    <tr>
                        <td><?= e($role['name']) ?></td>
                        <td><?= e($role['description'] ?? '') ?></td>
                        <td>
                            <?php $current = $permissionsByRole[(int)$role['id']] ?? []; ?>
                            <form method="post" action="/admin/roles/permissions" class="form-inline">
                                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                <input type="hidden" name="role_id" value="<?= e((string)$role['id']) ?>">
                                <div class="checkbox-grid">
                                    <?php foreach ($permissions as $permission) : ?>
                                        <label class="checkbox">
                                            <input
                                                type="checkbox"
                                                name="permission_ids[]"
                                                value="<?= e((string)$permission['id']) ?>"
                                                <?= in_array($permission['name'], $current, true) ? 'checked' : '' ?>
                                            >
                                            <span><?= e($permission['name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" class="button button-ghost">Save</button>
                            </form>
                        </td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
