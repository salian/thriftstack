<section class="page-section">
    <h1>Roles</h1>
    <p>Manage roles and their permissions.</p>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <div class="card">
        <div class="table-header">
            <h2>Existing roles</h2>
            <button type="button" class="button" data-modal-open="role-create">New Role</button>
        </div>
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
                            <form method="post" action="/super-admin/roles/permissions" class="form-inline">
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

    <dialog class="modal" id="role-create">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Create role</h2>
                    <p class="modal-subtitle">Add roles to group permissions.</p>
                </div>
                <button type="button" class="icon-button" data-modal-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="/super-admin/roles" class="form">
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <label>
                    <span>Role name</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>Description</span>
                    <input type="text" name="description">
                </label>
                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="button">Create role</button>
                </div>
            </form>
        </div>
    </dialog>
</section>
