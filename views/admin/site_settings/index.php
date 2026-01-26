<section class="page-section">
    <h1>App Super Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>Site Settings</h2>

    <div class="card">
        <h2>Access control</h2>
        <div class="tabs" data-tabs>
            <button type="button" class="tab-button is-active" data-tab-button="roles"
                aria-selected="true" aria-controls="tab-roles">App Roles</button>
            <button type="button" class="tab-button" data-tab-button="permissions"
                aria-selected="false" aria-controls="tab-permissions">App Permissions</button>
            <button type="button" class="tab-button" data-tab-button="workspace-roles"
                aria-selected="false" aria-controls="tab-workspace-roles">Workspace Roles</button>
            <button type="button" class="tab-button" data-tab-button="workspace-permissions"
                aria-selected="false" aria-controls="tab-workspace-permissions">Workspace Permissions</button>
        </div>

        <div class="tab-panels">
            <div class="tab-panel is-active" data-tab-panel="roles" id="tab-roles" role="tabpanel">
                <div class="table-header">
                    <h3>Existing app roles</h3>
                    <button type="button" class="button" data-modal-open="role-create">New App Role</button>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>App role</th>
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

            <div class="tab-panel" data-tab-panel="permissions" id="tab-permissions" role="tabpanel" hidden>
                <div class="table-header">
                    <h3>Existing app permissions</h3>
                    <button type="button" class="button" data-modal-open="permission-create">Create New Permission</button>
                </div>
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

            <div class="tab-panel" data-tab-panel="workspace-roles" id="tab-workspace-roles" role="tabpanel" hidden>
                <div class="table-header">
                    <h3>Workspace roles</h3>
                    <span class="muted">Manage workspace role permissions.</span>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Workspace role</th>
                            <th>Permissions</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($workspaceRoles ?? []) as $workspaceRole) : ?>
                            <tr>
                                <td><?= e($workspaceRole) ?></td>
                                <td>
                                    <?php $current = $workspacePermissionsByRole[$workspaceRole] ?? []; ?>
                                    <form method="post" action="/super-admin/workspace-roles/permissions" class="form-inline">
                                        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                        <input type="hidden" name="workspace_role" value="<?= e($workspaceRole) ?>">
                                        <div class="checkbox-grid">
                                            <?php foreach (($workspacePermissions ?? []) as $permission) : ?>
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

            <div class="tab-panel" data-tab-panel="workspace-permissions" id="tab-workspace-permissions" role="tabpanel" hidden>
                <div class="table-header">
                    <h3>Existing workspace permissions</h3>
                    <button type="button" class="button" data-modal-open="workspace-permission-create">Create New Permission</button>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($workspacePermissions ?? []) as $permission) : ?>
                            <tr>
                                <td><?= e($permission['name']) ?></td>
                                <td><?= e($permission['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <dialog class="modal" id="permission-create">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Create permission</h2>
                    <p class="modal-subtitle">Add app permissions to control access.</p>
                </div>
                <button type="button" class="icon-button" data-modal-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="/super-admin/permissions" class="form">
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <label>
                    <span>App permission name</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>Description</span>
                    <input type="text" name="description">
                </label>
                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="button">Create permission</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="modal" id="role-create">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Create app role</h2>
                    <p class="modal-subtitle">Add app roles to group permissions.</p>
                </div>
                <button type="button" class="icon-button" data-modal-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="/super-admin/roles" class="form">
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <label>
                    <span>App role name</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>Description</span>
                    <input type="text" name="description">
                </label>
                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="button">Create app role</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="modal" id="workspace-permission-create">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Create workspace permission</h2>
                    <p class="modal-subtitle">Add workspace permissions to control access.</p>
                </div>
                <button type="button" class="icon-button" data-modal-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="/super-admin/workspace-permissions" class="form">
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <label>
                    <span>Workspace permission name</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>Description</span>
                    <input type="text" name="description">
                </label>
                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="button">Create permission</button>
                </div>
            </form>
        </div>
    </dialog>

    <div class="card">
        <h2>Feature flags</h2>
        <p>App-wide enable/disable for sections like Tasks, Reports, and more (coming soon).</p>
    </div>
</section>
